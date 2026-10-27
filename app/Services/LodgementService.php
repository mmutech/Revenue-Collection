<?php

namespace App\Services;

use App\Enums\DiscrepancyType;
use App\Enums\LodgementStatus;
use App\Enums\TreasuryDecision;
use App\Events\LodgementConfirmed;
use App\Events\LodgementSubmitted;
use App\Exceptions\FinancialRuleViolation;
use App\Models\Bank;
use App\Models\BankLodgement;
use App\Models\BankLodgementDocument;
use App\Models\BankLodgementItem;
use App\Models\CollectionBatch;
use App\Models\TreasuryVerification;
use App\Models\User;
use App\Support\Setting;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LodgementService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly CollectionBatchService $batches,
        private readonly DiscrepancyService $discrepancies,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array{batch_id: int, amount: int|float|string}>  $allocations
     */
    public function create(User $actor, array $data, array $allocations): BankLodgement
    {
        Gate::forUser($actor)->authorize('create', BankLodgement::class);

        $branchId = (int) ($data['branch_id'] ?? $actor->branch_id);

        if (! $actor->canAccessBranch($branchId)) {
            throw new AuthorizationException('You are not authorised for the selected branch.');
        }

        $this->validateHeader($data, $branchId);

        if ($allocations === []) {
            throw ValidationException::withMessages(['allocations' => 'Allocate at least one approved collection batch.']);
        }

        $lodgement = DB::transaction(function () use ($actor, $data, $allocations, $branchId) {
            [$total, $batchIds] = $this->prepareAllocations($allocations);

            $lodgement = BankLodgement::query()->create([
                'lodgement_number' => $this->numbers->lodgementNumber(),
                'bank_id' => $data['bank_id'],
                'bank_branch_id' => $data['bank_branch_id'] ?? null,
                'branch_id' => $branchId,
                'deposit_slip_number' => $data['deposit_slip_number'],
                'deposit_date' => $data['deposit_date'],
                'amount' => number_format($total, 2, '.', ''),
                'currency' => $data['currency'] ?? Setting::string('organisation.currency', 'NGN'),
                'deposited_by_name' => $data['deposited_by_name'],
                'deposited_by_user_id' => $data['deposited_by_user_id'] ?? $actor->id,
                'created_by' => $actor->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($batchIds as $batchId => $amount) {
                BankLodgementItem::query()->create([
                    'bank_lodgement_id' => $lodgement->id,
                    'collection_batch_id' => $batchId,
                    'amount' => $amount,
                ]);
            }

            foreach (array_keys($batchIds) as $batchId) {
                $this->batches->refreshLodgementStatus(CollectionBatch::query()->findOrFail($batchId));
            }

            return $lodgement->refresh();
        });

        return $lodgement->load(['bank', 'branch', 'items.batch']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array{batch_id: int, amount: int|float|string}>  $allocations
     */
    public function update(BankLodgement $lodgement, User $actor, array $data, array $allocations): BankLodgement
    {
        Gate::forUser($actor)->authorize('create', BankLodgement::class);

        if (! $actor->canAccessBranch($lodgement->branch_id)) {
            throw new AuthorizationException('You are not authorised for this branch.');
        }

        $this->validateHeader($data, (int) $lodgement->branch_id, $lodgement->id);

        if ($allocations === []) {
            throw ValidationException::withMessages(['allocations' => 'Allocate at least one approved collection batch.']);
        }

        $lodgement = DB::transaction(function () use ($lodgement, $data, $allocations) {
            /** @var BankLodgement $lodgement */
            $lodgement = BankLodgement::query()->lockForUpdate()->findOrFail($lodgement->id);

            if (! $lodgement->canBeEdited()) {
                throw FinancialRuleViolation::because('Only draft or returned lodgements can be edited.');
            }

            if ($lodgement->status === LodgementStatus::Returned) {
                $lodgement->transitionTo(LodgementStatus::Draft, null, 'Lodgement reopened for correction');
            }

            $oldBatchIds = $lodgement->items()->pluck('collection_batch_id')->all();
            $lodgement->items()->delete();

            [$total, $batchIds] = $this->prepareAllocations($allocations, $lodgement->id);

            $lodgement->fill([
                'bank_id' => $data['bank_id'],
                'bank_branch_id' => $data['bank_branch_id'] ?? null,
                'deposit_slip_number' => $data['deposit_slip_number'],
                'deposit_date' => $data['deposit_date'],
                'amount' => number_format($total, 2, '.', ''),
                'deposited_by_name' => $data['deposited_by_name'],
                'notes' => $data['notes'] ?? null,
                'decision_notes' => null,
            ])->save();

            foreach ($batchIds as $batchId => $amount) {
                BankLodgementItem::query()->create([
                    'bank_lodgement_id' => $lodgement->id,
                    'collection_batch_id' => $batchId,
                    'amount' => $amount,
                ]);
            }

            foreach (array_unique(array_merge($oldBatchIds, array_keys($batchIds))) as $batchId) {
                $this->batches->refreshLodgementStatus(CollectionBatch::query()->findOrFail($batchId));
            }

            return $lodgement->refresh();
        });

        return $lodgement->load(['bank', 'branch', 'items.batch']);
    }

    public function submit(BankLodgement $lodgement, User $actor): BankLodgement
    {
        Gate::forUser($actor)->authorize('submit', $lodgement);

        $lodgement = DB::transaction(function () use ($lodgement, $actor) {
            /** @var BankLodgement $lodgement */
            $lodgement = BankLodgement::query()->lockForUpdate()->findOrFail($lodgement->id);

            if (! $lodgement->canBeEdited()) {
                throw FinancialRuleViolation::because('Only draft or returned lodgements can be submitted.');
            }

            $items = $lodgement->items()->get();
            if ($items->isEmpty()) {
                throw FinancialRuleViolation::because('A lodgement must allocate at least one batch.');
            }

            if (abs((float) $lodgement->amount - (float) $items->sum('amount')) >= 0.01) {
                throw FinancialRuleViolation::because('The lodgement amount does not match its allocations.');
            }

            if (Setting::bool('lodgements.require_document', true) && $lodgement->documents()->count() === 0) {
                throw ValidationException::withMessages([
                    'document' => 'Attach the deposit slip or bank evidence before submitting.',
                ]);
            }

            if ($lodgement->status === LodgementStatus::Returned) {
                $lodgement->transitionTo(LodgementStatus::Draft, $actor, 'Lodgement corrected');
            }

            $lodgement->fill([
                'submitted_at' => now(),
                'submitted_by' => $actor->id,
            ])->save();

            $lodgement->transitionTo(LodgementStatus::Submitted, $actor, 'Submitted to treasury');
            $lodgement->transitionTo(LodgementStatus::UnderTreasuryReview, $actor, 'Awaiting treasury confirmation');

            foreach ($lodgement->items as $item) {
                $this->batches->refreshLodgementStatus($item->batch);
            }

            return $lodgement->refresh();
        });

        LodgementSubmitted::dispatch($lodgement);

        return $lodgement;
    }

    /**
     * @param  array{decision: TreasuryDecision, deposited_amount?: int|float|string|null, bank_confirmation_reference?: string|null, notes?: string|null}  $data
     */
    public function decideTreasury(BankLodgement $lodgement, User $actor, array $data): BankLodgement
    {
        Gate::forUser($actor)->authorize('confirm', $lodgement);

        if ((int) $lodgement->created_by === (int) $actor->id || (int) $lodgement->submitted_by === (int) $actor->id) {
            throw FinancialRuleViolation::because('The lodgement creator or submitter cannot confirm treasury verification.');
        }

        $decision = $data['decision'];

        $lodgement = DB::transaction(function () use ($lodgement, $actor, $data, $decision) {
            /** @var BankLodgement $lodgement */
            $lodgement = BankLodgement::query()->lockForUpdate()->findOrFail($lodgement->id);

            if ($lodgement->status !== LodgementStatus::UnderTreasuryReview) {
                throw FinancialRuleViolation::because('This lodgement is not awaiting treasury review.');
            }

            $deposited = round((float) ($data['deposited_amount'] ?? $lodgement->amount), 2);
            $approved = round((float) $lodgement->amount, 2);
            $variance = round($deposited - $approved, 2);
            $notes = trim((string) ($data['notes'] ?? ''));

            if (in_array($decision, [TreasuryDecision::Returned, TreasuryDecision::Rejected, TreasuryDecision::Escalated], true)
                && $notes === '') {
                throw ValidationException::withMessages(['notes' => 'A reason is required for this treasury decision.']);
            }

            if ($decision === TreasuryDecision::Confirmed
                && $variance !== 0.0
                && Setting::bool('treasury.variance_requires_notes', true)
                && $notes === '') {
                throw ValidationException::withMessages([
                    'notes' => 'Explain the difference between the approved and deposited amounts.',
                ]);
            }

            if ($decision === TreasuryDecision::Confirmed
                && blank($data['bank_confirmation_reference'] ?? null)) {
                throw ValidationException::withMessages([
                    'bank_confirmation_reference' => 'A bank confirmation reference is required.',
                ]);
            }

            TreasuryVerification::query()->create([
                'bank_lodgement_id' => $lodgement->id,
                'officer_id' => $actor->id,
                'decision' => $decision->value,
                'approved_amount' => number_format($approved, 2, '.', ''),
                'deposited_amount' => number_format($deposited, 2, '.', ''),
                'variance' => number_format($variance, 2, '.', ''),
                'bank_confirmation_reference' => $data['bank_confirmation_reference'] ?? null,
                'notes' => $notes ?: null,
                'verified_at' => now(),
            ]);

            if ($decision === TreasuryDecision::Confirmed) {
                $lodgement->fill([
                    'treasury_verified_by' => $actor->id,
                    'treasury_verified_at' => now(),
                    'bank_confirmation_reference' => $data['bank_confirmation_reference'],
                    'decision_notes' => $notes ?: null,
                    'reconciled_at' => now(),
                ])->save();

                $lodgement->transitionTo(LodgementStatus::Verified, $actor, 'Deposit confirmed by treasury');
                $lodgement->transitionTo(LodgementStatus::Reconciled, $actor, 'Lodgement reconciled against treasury confirmation');

                if (abs($variance) >= 0.01) {
                    $this->discrepancies->create($actor, [
                        'lodgement_id' => $lodgement->id,
                        'branch_id' => $lodgement->branch_id,
                        'type' => DiscrepancyType::AmountMismatch,
                        'amount' => abs($variance),
                        'description' => 'Treasury variance on lodgement '.$lodgement->lodgement_number.': '.number_format($variance, 2).'. '.$notes,
                    ]);
                }

                foreach ($lodgement->items as $item) {
                    $this->batches->refreshLodgementStatus($item->batch);
                }
            } elseif ($decision === TreasuryDecision::Returned) {
                $lodgement->fill(['decision_notes' => $notes])->save();
                $lodgement->transitionTo(LodgementStatus::Returned, $actor, $notes);
                foreach ($lodgement->items as $item) {
                    $this->batches->refreshLodgementStatus($item->batch);
                }
            } elseif ($decision === TreasuryDecision::Rejected) {
                $lodgement->fill(['decision_notes' => $notes])->save();
                $lodgement->transitionTo(LodgementStatus::Rejected, $actor, $notes);
                foreach ($lodgement->items as $item) {
                    $this->batches->refreshLodgementStatus($item->batch);
                }
            } else {
                $lodgement->fill(['decision_notes' => $notes])->save();
                $this->discrepancies->create($actor, [
                    'lodgement_id' => $lodgement->id,
                    'branch_id' => $lodgement->branch_id,
                    'type' => DiscrepancyType::UnmatchedLodgement,
                    'amount' => abs($variance) > 0 ? abs($variance) : $approved,
                    'description' => 'Treasury escalated lodgement '.$lodgement->lodgement_number.'. '.$notes,
                ]);
            }

            return $lodgement->refresh();
        });

        if ($decision === TreasuryDecision::Confirmed) {
            LodgementConfirmed::dispatch($lodgement);
        }

        return $lodgement;
    }

    public function uploadDocument(BankLodgement $lodgement, User $actor, UploadedFile $file, ?string $documentType = null): BankLodgementDocument
    {
        Gate::forUser($actor)->authorize('create', BankLodgement::class);

        if (! $actor->canAccessBranch($lodgement->branch_id)) {
            throw new AuthorizationException('You are not authorised for this branch.');
        }

        if (! $lodgement->canBeEdited()) {
            throw FinancialRuleViolation::because('Evidence can only be added while the lodgement is editable.');
        }

        $validated = validator(['file' => $file], [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ])->validate();

        $path = $validated['file']->store('lodgements/'.$lodgement->id, 'local');

        return BankLodgementDocument::query()->create([
            'bank_lodgement_id' => $lodgement->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'checksum' => hash_file('sha256', $file->getRealPath()) ?: null,
            'uploaded_by' => $actor->id,
        ]);
    }

    public function deleteDocument(BankLodgementDocument $document, User $actor): void
    {
        Gate::forUser($actor)->authorize('create', BankLodgement::class);

        if (! $actor->canAccessBranch($document->lodgement->branch_id)) {
            throw new AuthorizationException('You are not authorised for this branch.');
        }

        if (! $document->lodgement->canBeEdited()) {
            throw FinancialRuleViolation::because('Evidence cannot be removed after the lodgement is submitted.');
        }

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateHeader(array $data, int $branchId, ?int $ignoreLodgementId = null): void
    {
        $bank = Bank::query()->find($data['bank_id'] ?? null);

        if (! $bank || ! $bank->is_active) {
            throw ValidationException::withMessages(['bank_id' => 'Select an active bank.']);
        }

        $duplicate = BankLodgement::query()
            ->where('bank_id', $bank->id)
            ->where('deposit_slip_number', $data['deposit_slip_number'] ?? null)
            ->when($ignoreLodgementId, fn ($q) => $q->whereKeyNot($ignoreLodgementId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'deposit_slip_number' => 'This deposit slip number has already been recorded for the selected bank.',
            ]);
        }

        if (! empty($data['bank_branch_id'])) {
            $validBranch = $bank->bankBranches()->whereKey($data['bank_branch_id'])->exists();
            if (! $validBranch) {
                throw ValidationException::withMessages(['bank_branch_id' => 'The bank branch does not belong to the selected bank.']);
            }
        }

        if (! $branchId) {
            throw ValidationException::withMessages(['branch_id' => 'A branch is required.']);
        }
    }

    /**
     * @param  array<int, array{batch_id: int, amount: int|float|string}>  $allocations
     * @return array{0: float, 1: array<int, string>}
     */
    private function prepareAllocations(array $allocations, ?int $ignoreLodgementId = null): array
    {
        $batchIds = [];
        foreach ($allocations as $allocation) {
            $batchId = (int) $allocation['batch_id'];
            $amount = round((float) $allocation['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['allocations' => 'Each allocation must be greater than zero.']);
            }

            if (isset($batchIds[$batchId])) {
                throw ValidationException::withMessages(['allocations' => 'A batch can only be allocated once per lodgement.']);
            }

            $batchIds[$batchId] = number_format($amount, 2, '.', '');
        }

        ksort($batchIds);

        $locked = CollectionBatch::query()
            ->whereIn('id', array_keys($batchIds))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($locked->count() !== count($batchIds)) {
            throw ValidationException::withMessages(['allocations' => 'One or more selected batches no longer exist.']);
        }

        $total = 0.0;

        foreach ($batchIds as $batchId => $amount) {
            /** @var CollectionBatch $batch */
            $batch = $locked->get($batchId);

            if (! in_array($batch->status, [
                \App\Enums\BatchStatus::ApprovedForLodgement,
                \App\Enums\BatchStatus::PartiallyLodged,
                \App\Enums\BatchStatus::FullyLodged,
            ], true)) {
                throw FinancialRuleViolation::because('Batch '.$batch->batch_number.' is not approved for lodgement.');
            }

            if ($batch->status === \App\Enums\BatchStatus::FullyLodged && $ignoreLodgementId === null) {
                throw FinancialRuleViolation::because('Batch '.$batch->batch_number.' is already fully lodged.');
            }

            $allocated = (float) BankLodgementItem::query()
                ->where('collection_batch_id', $batchId)
                ->when($ignoreLodgementId, fn ($q) => $q->where('bank_lodgement_id', '!=', $ignoreLodgementId))
                ->whereHas('lodgement', fn ($q) => $q->whereNotIn('status', [
                    LodgementStatus::Returned->value,
                    LodgementStatus::Rejected->value,
                ]))
                ->sum('amount');

            $available = round((float) $batch->expected_total - $allocated, 2);

            if ((float) $amount > $available + 0.001) {
                throw FinancialRuleViolation::because(
                    'Allocation of '.number_format((float) $amount, 2).' exceeds the available '
                    .number_format($available, 2).' on batch '.$batch->batch_number.'.'
                );
            }

            $total += (float) $amount;
        }

        return [round($total, 2), $batchIds];
    }
}
