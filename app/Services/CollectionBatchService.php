<?php

namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\ChequeStatus;
use App\Enums\DiscrepancyType;
use App\Enums\PaymentStatus;
use App\Enums\VerificationDecision;
use App\Events\CollectionBatchApproved;
use App\Events\CollectionBatchReturned;
use App\Events\CollectionBatchSubmitted;
use App\Events\CollectionBatchVerified;
use App\Exceptions\FinancialRuleViolation;
use App\Models\CashierShift;
use App\Models\CollectionBatch;
use App\Models\CollectionBatchItem;
use App\Models\CollectionVerification;
use App\Models\Payment;
use App\Models\User;
use App\Support\Setting;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CollectionBatchService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly DiscrepancyService $discrepancies,
    ) {}

    /**
     * @param  array{branch_id?: int, collection_point_id?: int|null, business_date?: string, shift_id?: int|null}  $data
     */
    public function create(User $actor, array $data): CollectionBatch
    {
        Gate::forUser($actor)->authorize('create', CollectionBatch::class);

        $branchId = (int) ($data['branch_id'] ?? $actor->branch_id);

        if (! $actor->canAccessBranch($branchId)) {
            throw new AuthorizationException('You are not authorised for the selected branch.');
        }

        return DB::transaction(function () use ($actor, $data, $branchId) {
            $shift = null;

            if (! empty($data['shift_id'])) {
                $shift = CashierShift::query()->lockForUpdate()->findOrFail($data['shift_id']);
            } else {
                $shift = $actor->openShift();
            }

            $businessDate = $data['business_date']
                ?? $shift?->business_date?->toDateString()
                ?? today()->toDateString();

            if ($shift && (int) $shift->branch_id !== $branchId) {
                throw ValidationException::withMessages(['shift_id' => 'The shift belongs to another branch.']);
            }

            $exists = CollectionBatch::query()
                ->where('cashier_id', $actor->id)
                ->whereDate('business_date', $businessDate)
                ->exists();

            if ($exists) {
                throw FinancialRuleViolation::because('A collection batch already exists for this cashier and business date.');
            }

            $payments = Payment::query()
                ->where('branch_id', $branchId)
                ->where('cashier_id', $actor->id)
                ->whereDate('payment_date', $businessDate)
                ->whereIn('status', [PaymentStatus::Received->value, PaymentStatus::Verified->value])
                ->whereDoesntHave('batchItem')
                ->where(function ($q) {
                    $q->whereNull('cheque_status')
                        ->orWhere('cheque_status', ChequeStatus::Cleared->value);
                })
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            if ($payments->isEmpty()) {
                throw FinancialRuleViolation::because('There are no unbatched payments for the selected business date.');
            }

            $total = round((float) $payments->sum('amount'), 2);

            $batch = CollectionBatch::query()->create([
                'batch_number' => $this->numbers->batchNumber(),
                'cashier_id' => $actor->id,
                'branch_id' => $branchId,
                'collection_point_id' => $data['collection_point_id'] ?? $shift?->collection_point_id ?? $actor->collection_point_id,
                'cashier_shift_id' => $shift?->id,
                'business_date' => $businessDate,
                'expected_total' => number_format($total, 2, '.', ''),
                'item_count' => $payments->count(),
            ]);

            $batch->transitionTo(BatchStatus::Draft, $actor, 'Collection batch created');

            foreach ($payments as $payment) {
                CollectionBatchItem::query()->create([
                    'collection_batch_id' => $batch->id,
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                ]);
            }

            return $batch->refresh()->load(['items.payment.channel', 'cashier', 'branch']);
        });
    }

    public function addPayment(CollectionBatch $batch, Payment $payment, User $actor): CollectionBatch
    {
        Gate::forUser($actor)->authorize('submit', $batch);

        return DB::transaction(function () use ($batch, $payment, $actor) {
            /** @var CollectionBatch $batch */
            $batch = CollectionBatch::query()->lockForUpdate()->findOrFail($batch->id);
            /** @var Payment $payment */
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (! $batch->status->isEditable()) {
                throw FinancialRuleViolation::because('Payments can only be changed while a batch is editable.');
            }

            if ((int) $payment->cashier_id !== (int) $batch->cashier_id
                || (int) $payment->branch_id !== (int) $batch->branch_id) {
                throw FinancialRuleViolation::because('The payment does not belong to this cashier and branch.');
            }

            if (! in_array($payment->status, [PaymentStatus::Received, PaymentStatus::Verified], true) || $payment->requiresChequeClearance()) {
                throw FinancialRuleViolation::because('Only received, cleared funds can be added to a batch.');
            }

            if ($payment->batchItem()->exists()) {
                throw FinancialRuleViolation::because('This payment is already included in a collection batch.');
            }

            CollectionBatchItem::query()->create([
                'collection_batch_id' => $batch->id,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
            ]);

            return $this->recalculate($batch);
        });
    }

    public function removePayment(CollectionBatch $batch, Payment $payment, User $actor): CollectionBatch
    {
        Gate::forUser($actor)->authorize('submit', $batch);

        return DB::transaction(function () use ($batch, $payment) {
            /** @var CollectionBatch $batch */
            $batch = CollectionBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if (! $batch->status->isEditable()) {
                throw FinancialRuleViolation::because('Payments can only be changed while a batch is editable.');
            }

            CollectionBatchItem::query()
                ->where('collection_batch_id', $batch->id)
                ->where('payment_id', $payment->id)
                ->delete();

            return $this->recalculate($batch);
        });
    }

    public function recalculate(CollectionBatch $batch): CollectionBatch
    {
        $total = (float) $batch->items()->sum('amount');
        $count = $batch->items()->count();

        $batch->forceFill([
            'expected_total' => number_format($total, 2, '.', ''),
            'item_count' => $count,
        ])->save();

        return $batch->refresh();
    }

    public function submit(CollectionBatch $batch, User $actor, int|float|string|null $declaredTotal = null): CollectionBatch
    {
        Gate::forUser($actor)->authorize('submit', $batch);

        if ((int) $batch->cashier_id !== (int) $actor->id && ! $actor->canAccessAllBranches()) {
            throw new AuthorizationException('Only the owning cashier can submit this batch.');
        }

        $batch = DB::transaction(function () use ($batch, $actor, $declaredTotal) {
            /** @var CollectionBatch $batch */
            $batch = CollectionBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if (! in_array($batch->status, [BatchStatus::Draft, BatchStatus::ReturnedForCorrection], true)) {
                throw FinancialRuleViolation::because('Only draft or returned batches can be submitted.');
            }

            $items = $batch->items()->with('payment')->get();

            if ($items->isEmpty()) {
                throw FinancialRuleViolation::because('A batch cannot be submitted without payments.');
            }

            foreach ($items as $item) {
                $payment = Payment::query()->lockForUpdate()->findOrFail($item->payment_id);

                if (! in_array($payment->status, [PaymentStatus::Received, PaymentStatus::Verified], true)) {
                    throw FinancialRuleViolation::because('Payment '.$payment->reference.' is no longer in a received state.');
                }

                if ($payment->cheque_status === ChequeStatus::PendingClearance) {
                    throw FinancialRuleViolation::because('Cheque '.$payment->cheque_number.' must clear before it can be batched.');
                }
            }

            $total = round((float) $items->sum('amount'), 2);
            $declared = $declaredTotal === null ? $total : round((float) $declaredTotal, 2);

            if ($declared < 0) {
                throw ValidationException::withMessages(['declared_total' => 'The declared total cannot be negative.']);
            }

            $batch->forceFill([
                'expected_total' => number_format($total, 2, '.', ''),
                'declared_total' => number_format($declared, 2, '.', ''),
                'item_count' => $items->count(),
                'submitted_at' => now(),
                'submitted_by' => $actor->id,
                'returned_at' => null,
                'return_reason' => null,
            ])->save();

            $batch->transitionTo(BatchStatus::Submitted, $actor, 'Submitted for clearing verification');
            $batch->transitionTo(BatchStatus::PendingVerification, $actor, 'Queued for clearing');

            return $batch->refresh();
        });

        CollectionBatchSubmitted::dispatch($batch);

        return $batch;
    }

    public function verify(
        CollectionBatch $batch,
        User $actor,
        VerificationDecision $decision,
        int|float|string $countedTotal,
        ?string $notes = null,
    ): CollectionBatch {
        Gate::forUser($actor)->authorize('verify', $batch);

        $batch = DB::transaction(function () use ($batch, $actor, $decision, $countedTotal, $notes) {
            /** @var CollectionBatch $batch */
            $batch = CollectionBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if ($batch->status !== BatchStatus::PendingVerification) {
                throw FinancialRuleViolation::because('Only batches pending verification can be verified.');
            }

            if ((int) $batch->submitted_by === (int) $actor->id || (int) $batch->cashier_id === (int) $actor->id) {
                throw FinancialRuleViolation::because('You cannot verify a batch that you submitted or own.');
            }

            $expected = round((float) $batch->expected_total, 2);

            if ($decision === VerificationDecision::Verified && $countedTotal === null) {
                throw ValidationException::withMessages([
                    'counted_total' => 'The counted total is required to verify a batch.',
                ]);
            }

            $counted = $decision === VerificationDecision::Verified ? round((float) $countedTotal, 2) : null;
            $variance = $counted === null ? 0.0 : round($counted - $expected, 2);

            if ($decision === VerificationDecision::Verified
                && abs($variance) >= 0.01
                && Setting::bool('collections.variance_requires_notes', true)
                && trim((string) $notes) === '') {
                throw ValidationException::withMessages([
                    'notes' => 'An explanation is required when the counted total differs from the expected total.',
                ]);
            }

            CollectionVerification::query()->create([
                'collection_batch_id' => $batch->id,
                'verified_by' => $actor->id,
                'decision' => $decision->value,
                'expected_total' => $batch->expected_total,
                'counted_total' => $counted === null ? null : number_format($counted, 2, '.', ''),
                'variance' => number_format($variance, 2, '.', ''),
                'notes' => $notes,
                'created_at' => now(),
            ]);

            if ($decision === VerificationDecision::Verified) {
                $batch->forceFill([
                    'counted_total' => number_format($counted, 2, '.', ''),
                    'variance' => number_format($variance, 2, '.', ''),
                    'verified_at' => now(),
                    'verified_by' => $actor->id,
                ])->save();

                $batch->transitionTo(BatchStatus::Verified, $actor, $notes ?: 'Collections verified');

                foreach ($batch->items as $item) {
                    /** @var Payment $payment */
                    $payment = Payment::query()->lockForUpdate()->findOrFail($item->payment_id);

                    if ($payment->status === PaymentStatus::Received) {
                        $payment->fill([
                            'verified_by' => $actor->id,
                            'verified_at' => now(),
                        ])->save();
                        $payment->transitionTo(PaymentStatus::Verified, $actor, 'Verified in batch '.$batch->batch_number);
                    }
                }

                if (abs($variance) >= 0.01) {
                    $this->discrepancies->create($actor, [
                        'batch_id' => $batch->id,
                        'branch_id' => $batch->branch_id,
                        'type' => $variance < 0 ? DiscrepancyType::Shortage : DiscrepancyType::Overage,
                        'amount' => abs($variance),
                        'description' => 'Verification variance for batch '.$batch->batch_number.': '.number_format($variance, 2).'. '.$notes,
                        'assigned_to' => $batch->cashier_id,
                    ]);
                }
            } else {
                $batch->fill([
                    'returned_at' => now(),
                    'return_reason' => $notes,
                ])->save();

                $batch->transitionTo(BatchStatus::ReturnedForCorrection, $actor, $notes ?: 'Returned for correction');

                return $batch->refresh();
            }

            return $batch->refresh();
        });

        if ($decision === VerificationDecision::Verified) {
            CollectionBatchVerified::dispatch($batch, abs((float) $batch->variance) >= 0.01);
        } else {
            CollectionBatchReturned::dispatch($batch, (string) $batch->return_reason);
        }

        return $batch;
    }

    public function approveForLodgement(CollectionBatch $batch, User $actor): CollectionBatch
    {
        Gate::forUser($actor)->authorize('approveForLodgement', $batch);

        if (Setting::bool('collections.require_separate_lodgement_approver', true)
            && (int) $batch->verified_by === (int) $actor->id) {
            throw FinancialRuleViolation::because('The verifier cannot also approve the same batch for lodgement.');
        }

        if ((int) $batch->cashier_id === (int) $actor->id) {
            throw FinancialRuleViolation::because('A cashier cannot approve their own batch for lodgement.');
        }

        $batch = DB::transaction(function () use ($batch, $actor) {
            /** @var CollectionBatch $batch */
            $batch = CollectionBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if ($batch->status !== BatchStatus::Verified) {
                throw FinancialRuleViolation::because('Only verified batches can be approved for lodgement.');
            }

            $batch->fill([
                'approved_at' => now(),
                'approved_by' => $actor->id,
            ])->save();

            $batch->transitionTo(BatchStatus::ApprovedForLodgement, $actor, 'Approved for bank lodgement');

            return $batch->refresh();
        });

        CollectionBatchApproved::dispatch($batch);

        return $batch;
    }

    public function close(CollectionBatch $batch, User $actor, ?string $notes = null): CollectionBatch
    {
        Gate::forUser($actor)->authorize('close', $batch);

        return DB::transaction(function () use ($batch, $actor, $notes) {
            /** @var CollectionBatch $batch */
            $batch = CollectionBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if ($batch->status !== BatchStatus::Reconciled) {
                throw FinancialRuleViolation::because('Only reconciled batches can be closed.');
            }

            $batch->forceFill([
                'closed_at' => now(),
                'closed_by' => $actor->id,
                'notes' => $notes ?? $batch->notes,
            ])->save();

            $batch->transitionTo(BatchStatus::Closed, $actor, $notes ?: 'Period closed');

            return $batch->refresh();
        });
    }

    public function refreshLodgementStatus(CollectionBatch $batch): CollectionBatch
    {
        return DB::transaction(function () use ($batch) {
            /** @var CollectionBatch $batch */
            $batch = CollectionBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if (! in_array($batch->status, [
                BatchStatus::ApprovedForLodgement,
                BatchStatus::PartiallyLodged,
                BatchStatus::FullyLodged,
                BatchStatus::TreasuryReview,
            ], true)) {
                return $batch;
            }

            $expected = round((float) $batch->expected_total, 2);
            $allocated = round((float) $batch->activeLodgementItems()->sum('amount'), 2);
            $confirmed = round((float) $batch->activeLodgementItems()
                ->whereHas('lodgement', fn ($q) => $q->whereIn('status', ['verified', 'reconciled', 'closed']))
                ->sum('amount'), 2);

            $submitted = $batch->activeLodgementItems()
                ->whereHas('lodgement', fn ($q) => $q->whereIn('status', ['submitted', 'under_treasury_review']))
                ->exists();

            if ($allocated < 0.01) {
                $target = BatchStatus::ApprovedForLodgement;
            } elseif ($allocated + 0.01 < $expected) {
                $target = BatchStatus::PartiallyLodged;
            } elseif ($confirmed + 0.01 >= $expected) {
                $target = BatchStatus::Reconciled;
            } elseif ($submitted) {
                $target = BatchStatus::TreasuryReview;
            } else {
                $target = BatchStatus::FullyLodged;
            }

            if ($target === $batch->status) {
                return $batch;
            }

            if ($batch->status === BatchStatus::FullyLodged && $target === BatchStatus::Reconciled) {
                $batch->transitionTo(BatchStatus::TreasuryReview, null, 'Treasury confirmation received');
            }

            $batch->transitionTo($target, null, 'Lodgement allocation recalculated');

            return $batch->refresh();
        });
    }
}
