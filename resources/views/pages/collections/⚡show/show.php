<?php

use App\Enums\BatchStatus;
use App\Enums\ChequeStatus;
use App\Enums\PaymentStatus;
use App\Enums\VerificationDecision;
use App\Exceptions\FinancialRuleViolation;
use App\Models\CollectionBatch;
use App\Models\Payment;
use App\Models\WorkflowTransition;
use App\Services\CollectionBatchService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('Collection batch')] class extends Component
{
    public CollectionBatch $batch;

    public string $verification_decision = 'verified';

    public string $counted_total = '';

    public string $verification_notes = '';

    public string $declared_total = '';

    public string $action_error = '';

    public function mount(CollectionBatch $batch): void
    {
        Gate::authorize('view', $batch);

        $this->batch = $batch->load([
            'cashier',
            'branch',
            'collectionPoint',
            'shift',
            'submittedBy',
            'verifiedBy',
            'approvedBy',
            'closedBy',
            'items.payment.channel',
            'latestVerification',
            'lodgementItems.lodgement.bank',
        ]);

        $this->counted_total = (string) $batch->expected_total;
        $this->declared_total = (string) ($batch->declared_total ?? $batch->expected_total);
    }

    public function addPayment(CollectionBatchService $batches, int $paymentId): void
    {
        $payment = Payment::query()->findOrFail($paymentId);

        try {
            $batches->addPayment($this->batch, $payment, auth()->user());
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->action_error = $exception->getMessage();
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');

            return;
        }

        $this->refreshBatch();
        $this->dispatch('toast', message: 'Payment added to the batch.', type: 'success');
    }

    public function removePayment(CollectionBatchService $batches, int $paymentId): void
    {
        $payment = Payment::query()->findOrFail($paymentId);

        try {
            $batches->removePayment($this->batch, $payment, auth()->user());
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->action_error = $exception->getMessage();
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');

            return;
        }

        $this->refreshBatch();
        $this->dispatch('toast', message: 'Payment removed from the batch.', type: 'success');
    }

    public function submit(CollectionBatchService $batches): void
    {
        $this->validate([
            'declared_total' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $batches->submit($this->batch, auth()->user(), $this->declared_total);
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->action_error = $exception->getMessage();
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');

            return;
        }

        $this->refreshBatch();
        $this->dispatch('toast', message: 'Batch submitted to clearing.', type: 'success');
    }

    public function verify(CollectionBatchService $batches): void
    {
        $this->validate([
            'verification_decision' => ['required', 'in:verified,returned'],
            'counted_total' => ['required_if:verification_decision,verified', 'nullable', 'numeric', 'min:0'],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $batches->verify(
                $this->batch,
                auth()->user(),
                $this->verification_decision === 'verified' ? VerificationDecision::Verified : VerificationDecision::Returned,
                $this->counted_total,
                $this->verification_notes ?: null,
            );
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->action_error = $exception->getMessage();
            $this->addError('verification_notes', $exception->getMessage());

            return;
        }

        $this->refreshBatch();
        $this->reset('verification_notes');
        $this->dispatch(
            'toast',
            message: $this->verification_decision === 'verified' ? 'Batch verified.' : 'Batch returned to the cashier.',
            type: $this->verification_decision === 'verified' ? 'success' : 'warning'
        );
    }

    public function approve(CollectionBatchService $batches): void
    {
        try {
            $batches->approveForLodgement($this->batch, auth()->user());
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->action_error = $exception->getMessage();
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');

            return;
        }

        $this->refreshBatch();
        $this->dispatch('toast', message: 'Batch approved for bank lodgement.', type: 'success');
    }

    public function close(CollectionBatchService $batches): void
    {
        try {
            $batches->close($this->batch, auth()->user());
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->action_error = $exception->getMessage();
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');

            return;
        }

        $this->refreshBatch();
        $this->dispatch('toast', message: 'Batch closed.', type: 'success');
    }

    private function refreshBatch(): void
    {
        $this->batch = $this->batch->fresh([
            'cashier',
            'branch',
            'collectionPoint',
            'shift',
            'submittedBy',
            'verifiedBy',
            'approvedBy',
            'closedBy',
            'items.payment.channel',
            'latestVerification',
            'lodgementItems.lodgement.bank',
        ]);
    }

    public function render()
    {
        $user = auth()->user();
        $batch = $this->batch;

        $availablePayments = $batch->canBeEdited()
            ? Payment::query()
                ->where('branch_id', $batch->branch_id)
                ->where('cashier_id', $batch->cashier_id)
                ->whereDate('payment_date', $batch->business_date)
                ->whereIn('status', [PaymentStatus::Received->value, PaymentStatus::Verified->value])
                ->whereDoesntHave('batchItem')
                ->where(fn ($q) => $q->whereNull('cheque_status')->orWhere('cheque_status', ChequeStatus::Cleared->value))
                ->with('channel')
                ->orderBy('id')
                ->get()
            : collect();

        $transitions = WorkflowTransition::query()
            ->with('actor')
            ->where('subject_type', $batch->getMorphClass())
            ->where('subject_id', $batch->id)
            ->latest('created_at')
            ->get();

        return view('pages.collections.⚡show.show', [
            'availablePayments' => $availablePayments,
            'transitions' => $transitions,
            'lodgementItems' => $batch->lodgementItems->loadMissing('lodgement.bank'),
            'canSubmit' => $batch->canBeEdited()
                && $user->can('submit', $batch)
                && ($user->id === $batch->cashier_id || $user->canAccessAllBranches()),
            'canVerify' => $batch->status === BatchStatus::PendingVerification
                && $user->can('verify', $batch)
                && $user->id !== $batch->cashier_id
                && $user->id !== $batch->submitted_by,
            'canApprove' => $batch->status === BatchStatus::Verified && $user->can('approveForLodgement', $batch),
            'canClose' => $batch->status === BatchStatus::Reconciled && $user->can('close', $batch),
        ]);
    }
};
