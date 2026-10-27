<?php

use App\Enums\LodgementStatus;
use App\Exceptions\FinancialRuleViolation;
use App\Models\BankLodgement;
use App\Services\LodgementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')]
#[Title('Bank lodgement')] class extends Component
{
    use WithFileUploads;

    public BankLodgement $lodgement;

    public $document = null;

    public string $document_type = 'deposit_slip';

    public string $action_error = '';

    public function mount(BankLodgement $lodgement): void
    {
        Gate::authorize('view', $lodgement);

        $this->loadLodgement($lodgement);
    }

    public function uploadDocument(LodgementService $lodgements): void
    {
        $this->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_type' => ['required', 'string', 'max:40'],
        ]);

        try {
            $lodgements->uploadDocument($this->lodgement, auth()->user(), $this->document, $this->document_type);
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->action_error = $exception->getMessage();
            $this->addError('document', $exception->getMessage());

            return;
        }

        $this->reset('document');
        $this->loadLodgement($this->lodgement->fresh());
        $this->dispatch('toast', message: 'Evidence attached.', type: 'success');
    }

    public function deleteDocument(LodgementService $lodgements, int $documentId): void
    {
        $document = $this->lodgement->documents()->findOrFail($documentId);

        try {
            $lodgements->deleteDocument($document, auth()->user());
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->action_error = $exception->getMessage();
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');

            return;
        }

        $this->loadLodgement($this->lodgement->fresh());
        $this->dispatch('toast', message: 'Evidence removed.', type: 'success');
    }

    public function submit(LodgementService $lodgements): void
    {
        try {
            $lodgements->submit($this->lodgement, auth()->user());
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->action_error = $exception->getMessage();
            $this->addError('document', $exception->getMessage());
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');

            return;
        }

        $this->loadLodgement($this->lodgement->fresh());
        $this->dispatch('toast', message: 'Lodgement submitted to treasury.', type: 'success');
    }

    private function loadLodgement(BankLodgement $lodgement): void
    {
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
            'discrepancies',
        ]);
    }

    public function render()
    {
        $user = auth()->user();

        return view('pages.lodgements.⚡show.show', [
            'canEditEvidence' => $this->lodgement->canBeEdited()
                && $user->can('create', BankLodgement::class)
                && $user->canAccessBranch($this->lodgement->branch_id),
            'canSubmit' => $this->lodgement->canBeEdited()
                && $user->can('submit', $this->lodgement),
            'canConfirm' => $this->lodgement->status === LodgementStatus::UnderTreasuryReview
                && $user->can('confirm', $this->lodgement),
        ]);
    }
};
