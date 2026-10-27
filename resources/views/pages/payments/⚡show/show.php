<?php

use App\Enums\AdjustmentStatus;
use App\Enums\ChequeStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\FinancialRuleViolation;
use App\Models\Payment;
use App\Models\WorkflowTransition;
use App\Services\PaymentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('Payment detail')] class extends Component
{
    public Payment $payment;

    public string $cancel_reason = '';

    public string $cheque_decision = 'cleared';

    public string $cheque_notes = '';

    public string $reversal_reason = '';

    public string $decision_notes = '';

    public function mount(Payment $payment): void
    {
        Gate::authorize('view', $payment);

        $this->payment = $payment->load([
            'channel',
            'branch',
            'collectionPoint',
            'cashier',
            'creator',
            'verifiedBy',
            'receipt.issuedBy',
            'batchItem.batch',
            'adjustments.requester',
            'adjustments.decider',
            'bank',
            'bankBranch',
        ]);
    }

    public function cancel(PaymentService $payments): void
    {
        $this->validate(['cancel_reason' => ['required', 'string', 'min:5', 'max:1000']]);

        try {
            $payments->cancel($this->payment, auth()->user(), $this->cancel_reason);
        } catch (FinancialRuleViolation|AuthorizationException $exception) {
            $this->addError('cancel_reason', $exception->getMessage());

            return;
        }

        $this->payment->refresh();
        $this->reset('cancel_reason');
        $this->dispatch('toast', message: 'Payment cancelled.', type: 'success');
    }

    public function clearCheque(PaymentService $payments): void
    {
        $this->validate([
            'cheque_decision' => ['required', 'in:cleared,dishonoured'],
            'cheque_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $payments->clearCheque(
                $this->payment,
                auth()->user(),
                $this->cheque_decision === 'cleared' ? ChequeStatus::Cleared : ChequeStatus::Dishonoured,
                $this->cheque_notes
            );
        } catch (FinancialRuleViolation|AuthorizationException $exception) {
            $this->addError('cheque_notes', $exception->getMessage());

            return;
        }

        $this->payment->refresh();
        $this->reset('cheque_notes');
        $this->dispatch('toast', message: 'Cheque outcome recorded.', type: 'success');
    }

    public function requestReversal(PaymentService $payments): void
    {
        $this->validate(['reversal_reason' => ['required', 'string', 'min:10', 'max:1000']]);

        try {
            $payments->requestReversal($this->payment, auth()->user(), $this->reversal_reason);
        } catch (FinancialRuleViolation|AuthorizationException $exception) {
            $this->addError('reversal_reason', $exception->getMessage());

            return;
        }

        $this->payment->refresh();
        $this->reset('reversal_reason');
        $this->dispatch('toast', message: 'Reversal request submitted for approval.', type: 'success');
    }

    public function decideReversal(PaymentService $payments, int $adjustmentId, bool $approved): void
    {
        $adjustment = $this->payment->adjustments()->findOrFail($adjustmentId);

        if (! $approved && trim($this->decision_notes) === '') {
            $this->addError('decision_notes', 'Provide a reason when rejecting a reversal.');

            return;
        }

        try {
            $payments->decideReversal($adjustment, auth()->user(), $approved, $this->decision_notes ?: null);
        } catch (FinancialRuleViolation|AuthorizationException $exception) {
            $this->addError('decision_notes', $exception->getMessage());

            return;
        }

        $this->payment->refresh();
        $this->reset('decision_notes');
        $this->dispatch('toast', message: $approved ? 'Reversal approved and applied.' : 'Reversal rejected.', type: 'success');
    }

    public function render()
    {
        $transitions = WorkflowTransition::query()
            ->with('actor')
            ->where('subject_type', $this->payment->getMorphClass())
            ->where('subject_id', $this->payment->id)
            ->latest('created_at')
            ->get();

        return view('pages.payments.⚡show.show', [
            'transitions' => $transitions,
            'pendingAdjustments' => $this->payment->adjustments->filter(
                fn ($adjustment) => $adjustment->status === AdjustmentStatus::Pending
            ),
            'canCancel' => auth()->user()->can('cancel', $this->payment)
                && in_array($this->payment->status, [PaymentStatus::Pending, PaymentStatus::Received], true)
                && $this->payment->batchItem === null,
            'canClearCheque' => auth()->user()->can('clearCheque', $this->payment)
                && $this->payment->isCheque()
                && in_array($this->payment->cheque_status, [ChequeStatus::Received, ChequeStatus::PendingClearance], true),
            'canReverse' => auth()->user()->can('reverse', $this->payment)
                && in_array($this->payment->status, [PaymentStatus::Received, PaymentStatus::Verified], true)
                && $this->payment->batchItem === null,
            'canApproveReversal' => auth()->user()->can('approveReversal', $this->payment),
        ]);
    }
};
