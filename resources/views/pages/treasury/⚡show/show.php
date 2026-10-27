<?php

use App\Enums\LodgementStatus;
use App\Enums\TreasuryDecision;
use App\Exceptions\FinancialRuleViolation;
use App\Models\BankLodgement;
use App\Services\LodgementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('Treasury review')] class extends Component
{
    public BankLodgement $lodgement;

    public string $decision = 'confirmed';

    public string $deposited_amount = '';

    public string $bank_confirmation_reference = '';

    public string $notes = '';

    public string $action_error = '';

    public function mount(BankLodgement $lodgement): void
    {
        Gate::authorize('view', $lodgement);
        abort_unless(auth()->user()->can('treasury.view'), 403);

        $this->lodgement = $lodgement->load([
            'bank',
            'bankBranch',
            'branch',
            'creator',
            'submittedBy',
            'treasuryVerifiedBy',
            'items.batch.cashier',
            'documents',
            'treasuryVerifications.officer',
            'latestTreasuryVerification',
        ]);

        $this->deposited_amount = (string) $lodgement->amount;
    }

    public function decide(LodgementService $lodgements): void
    {
        $this->action_error = '';

        $this->validate([
            'decision' => ['required', 'in:confirmed,returned,rejected,escalated'],
            'deposited_amount' => ['required', 'numeric', 'min:0'],
            'bank_confirmation_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $lodgements->decideTreasury($this->lodgement, auth()->user(), [
                'decision' => TreasuryDecision::from($this->decision),
                'deposited_amount' => $this->deposited_amount,
                'bank_confirmation_reference' => $this->bank_confirmation_reference ?: null,
                'notes' => $this->notes ?: null,
            ]);
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->action_error = $exception->getMessage();
            $this->addError('notes', $exception->getMessage());

            return;
        }

        $this->lodgement = $this->lodgement->fresh([
            'bank',
            'bankBranch',
            'branch',
            'creator',
            'submittedBy',
            'treasuryVerifiedBy',
            'items.batch.cashier',
            'documents',
            'treasuryVerifications.officer',
            'latestTreasuryVerification',
        ]);

        $this->reset('notes', 'bank_confirmation_reference');
        $this->dispatch('toast', message: 'Treasury decision recorded.', type: 'success');
    }

    public function render()
    {
        $user = auth()->user();
        $variance = round((float) $this->deposited_amount - (float) $this->lodgement->amount, 2);

        return view('pages.treasury.⚡show.show', [
            'variance' => $variance,
            'canDecide' => $this->lodgement->status === LodgementStatus::UnderTreasuryReview
                && $user->can('confirm', $this->lodgement)
                && $user->id !== $this->lodgement->created_by
                && $user->id !== $this->lodgement->submitted_by,
            'isOwnSubmission' => $user->id === $this->lodgement->created_by || $user->id === $this->lodgement->submitted_by,
        ]);
    }
};
