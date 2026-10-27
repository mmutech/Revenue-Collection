<?php

namespace App\Services;

use App\Enums\DiscrepancyType;
use App\Enums\PaymentStatus;
use App\Enums\ReconciliationItemStatus;
use App\Enums\ReconciliationStatus;
use App\Events\ReconciliationCompleted;
use App\Exceptions\FinancialRuleViolation;
use App\Models\BankLodgement;
use App\Models\BankLodgementItem;
use App\Models\CollectionDiscrepancy;
use App\Models\Payment;
use App\Models\ReconciliationItem;
use App\Models\ReconciliationRun;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReconciliationService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly DiscrepancyService $discrepancies,
    ) {}

    /**
     * @param  array{period_start: string, period_end: string, branch_id?: int|null}  $data
     */
    public function run(User $actor, array $data): ReconciliationRun
    {
        Gate::forUser($actor)->authorize('create', ReconciliationRun::class);

        $branchId = ! empty($data['branch_id']) ? (int) $data['branch_id'] : null;

        if ($branchId && ! $actor->canAccessBranch($branchId)) {
            throw new AuthorizationException('You are not authorised for the selected branch.');
        }

        if (! $branchId && ! $actor->canAccessAllBranches()) {
            throw ValidationException::withMessages(['branch_id' => 'Select a branch for reconciliation.']);
        }

        if ($data['period_end'] < $data['period_start']) {
            throw ValidationException::withMessages(['period_end' => 'The end date must not be before the start date.']);
        }

        $run = ReconciliationRun::query()->create([
            'reference' => $this->numbers->reconciliationReference(),
            'type' => 'collections_vs_lodgements',
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'branch_id' => $branchId,
            'created_by' => $actor->id,
            'status' => ReconciliationStatus::Pending->value,
        ]);

        try {
            $this->execute($run, $actor);
        } catch (\Throwable $e) {
            $run->forceFill([
                'status' => ReconciliationStatus::Failed->value,
                'error' => mb_substr($e->getMessage(), 0, 2000),
                'completed_at' => now(),
            ])->save();

            throw $e;
        }

        return $run->refresh();
    }

    public function execute(ReconciliationRun $run, ?User $actor = null): ReconciliationRun
    {
        $run->forceFill([
            'status' => ReconciliationStatus::Running->value,
            'started_at' => now(),
        ])->save();

        $run = DB::transaction(function () use ($run, $actor) {
            $query = Payment::query()
                ->with(['batchItem.batch', 'channel'])
                ->where('status', PaymentStatus::Verified->value)
                ->whereBetween('payment_date', [$run->period_start->toDateString(), $run->period_end->toDateString()])
                ->when($run->branch_id, fn ($q) => $q->where('branch_id', $run->branch_id));

            $paymentCount = (clone $query)->count();
            $batchIds = (clone $query)
                ->join('collection_batch_items', 'collection_batch_items.payment_id', '=', 'payments.id')
                ->distinct()
                ->pluck('collection_batch_items.collection_batch_id')
                ->all();

            $allocated = BankLodgementItem::query()
                ->whereIn('collection_batch_id', $batchIds ?: [0])
                ->whereHas('lodgement', fn ($q) => $q->whereNotIn('status', ['returned', 'rejected']))
                ->groupBy('collection_batch_id')
                ->selectRaw('collection_batch_id, SUM(amount) as total')
                ->pluck('total', 'collection_batch_id');

            $confirmed = BankLodgementItem::query()
                ->whereIn('collection_batch_id', $batchIds ?: [0])
                ->whereHas('lodgement', fn ($q) => $q->whereIn('status', ['verified', 'reconciled', 'closed']))
                ->groupBy('collection_batch_id')
                ->selectRaw('collection_batch_id, SUM(amount) as total')
                ->pluck('total', 'collection_batch_id');

            $matched = 0;
            $unmatched = 0;
            $expected = 0.0;
            $actual = 0.0;
            $byChannel = [];

            $query->chunkById(500, function ($payments) use (&$matched, &$unmatched, &$expected, &$actual, &$byChannel, $run, $confirmed, $allocated, $actor) {
                foreach ($payments as $payment) {
                    /** @var Payment $payment */
                    $batch = $payment->batchItem?->batch;
                    $isMatched = false;

                    if ($batch) {
                        $confirmedAmount = (float) ($confirmed[$batch->id] ?? 0);
                        $isMatched = $confirmedAmount + 0.01 >= (float) $batch->expected_total;
                    }

                    $amount = (float) $payment->amount;
                    $expected += $amount;

                    if ($isMatched) {
                        $actual += $amount;
                        $matched++;
                    } else {
                        $unmatched++;
                    }

                    $channelCode = $payment->channel?->code ?? 'unknown';
                    $byChannel[$channelCode] = ($byChannel[$channelCode] ?? 0) + $amount;

                    ReconciliationItem::query()->create([
                        'reconciliation_run_id' => $run->id,
                        'type' => 'payment',
                        'payment_id' => $payment->id,
                        'collection_batch_id' => $batch?->id,
                        'expected_amount' => number_format($amount, 2, '.', ''),
                        'actual_amount' => number_format($isMatched ? $amount : 0, 2, '.', ''),
                        'variance' => number_format($isMatched ? 0 : -$amount, 2, '.', ''),
                        'status' => ($isMatched ? ReconciliationItemStatus::Matched : ReconciliationItemStatus::Unmatched)->value,
                        'notes' => $isMatched
                            ? 'Batch fully lodged and treasury confirmed.'
                            : ($batch ? 'Treasury confirmation incomplete for batch '.$batch->batch_number.'.' : 'Payment has not been included in a collection batch.'),
                    ]);

                    if (! $isMatched && ! $this->hasOpenDiscrepancy($payment)) {
                        $this->discrepancies->create($actor ?? $run->creator, [
                            'payment_id' => $payment->id,
                            'batch_id' => $batch?->id,
                            'branch_id' => $payment->branch_id,
                            'type' => DiscrepancyType::MissingTransaction,
                            'amount' => $amount,
                            'description' => 'Verified payment '.$payment->reference.' is not matched by a treasury-confirmed lodgement in run '.$run->reference.'.',
                        ]);
                    }
                }
            });

            $lodgementQuery = BankLodgement::query()
                ->with('items')
                ->whereIn('status', ['verified', 'reconciled', 'closed'])
                ->whereBetween('deposit_date', [$run->period_start->toDateString(), $run->period_end->toDateString()])
                ->when($run->branch_id, fn ($q) => $q->where('branch_id', $run->branch_id));

            $lodgementQuery->chunkById(500, function ($lodgements) use ($run, $actor) {
                foreach ($lodgements as $lodgement) {
                    $allocatedTotal = (float) $lodgement->items->sum('amount');
                    $variance = round((float) $lodgement->amount - $allocatedTotal, 2);
                    $status = abs($variance) < 0.01
                        ? ReconciliationItemStatus::Matched
                        : ReconciliationItemStatus::Variance;

                    ReconciliationItem::query()->create([
                        'reconciliation_run_id' => $run->id,
                        'type' => 'lodgement',
                        'bank_lodgement_id' => $lodgement->id,
                        'expected_amount' => number_format($allocatedTotal, 2, '.', ''),
                        'actual_amount' => number_format((float) $lodgement->amount, 2, '.', ''),
                        'variance' => number_format($variance, 2, '.', ''),
                        'status' => $status->value,
                        'notes' => 'Treasury-confirmed lodgement '.$lodgement->lodgement_number.'.',
                    ]);

                    if ($status === ReconciliationItemStatus::Variance) {
                        $this->discrepancies->create($actor ?? $run->creator, [
                            'lodgement_id' => $lodgement->id,
                            'branch_id' => $lodgement->branch_id,
                            'type' => DiscrepancyType::AmountMismatch,
                            'amount' => abs($variance),
                            'description' => 'Lodgement '.$lodgement->lodgement_number.' amount differs from its allocations by '.number_format($variance, 2).'.',
                        ]);
                    }
                }
            });

            $run->forceFill([
                'status' => ReconciliationStatus::Completed->value,
                'completed_at' => now(),
                'matched_count' => $matched,
                'unmatched_count' => $unmatched,
                'total_expected' => number_format($expected, 2, '.', ''),
                'total_actual' => number_format($actual, 2, '.', ''),
                'total_variance' => number_format($expected - $actual, 2, '.', ''),
                'summary' => [
                    'payments_examined' => $paymentCount,
                    'matched_payments' => $matched,
                    'unmatched_payments' => $unmatched,
                    'by_channel' => $byChannel,
                ],
                'error' => null,
            ])->save();

            return $run->refresh();
        });

        ReconciliationCompleted::dispatch($run);

        return $run;
    }

    private function hasOpenDiscrepancy(Payment $payment): bool
    {
        return CollectionDiscrepancy::query()
            ->where('payment_id', $payment->id)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->exists();
    }
}
