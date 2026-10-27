<?php

use App\Models\AccountTreatmentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('Treatment request detail')] class extends Component
{
    public AccountTreatmentRequest $treatment;

    public function mount(AccountTreatmentRequest $treatment): void
    {
        Gate::authorize('view', $treatment);

        $this->treatment = $treatment->load([
            'account',
            'branch',
            'type',
            'reason',
            'requester',
            'assignee',
            'payment',
            'collectionBatch',
            'lodgement',
            'reconciliationRun',
            'approvals.approver',
            'documents.uploader',
        ]);
    }

    public function render(): View
    {
        return view('pages.treatment.⚡show.show');
    }
};
