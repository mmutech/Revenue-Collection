<?php

use App\Models\AccountTreatmentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Treatment requests')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', AccountTreatmentRequest::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $user = auth()->user();
        $requests = AccountTreatmentRequest::query()
            ->with(['account', 'branch', 'type', 'requester'])
            ->when(! $user->canAccessAllBranches(), fn ($query) => $query->whereIn('branch_id', $user->accessibleBranchIds()))
            ->search($this->search)
            ->latest()
            ->paginate(20);

        return view('pages.treatment.⚡index.index', [
            'requests' => $requests,
        ]);
    }
};
