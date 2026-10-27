<?php

use App\Enums\LodgementStatus;
use App\Models\BankLodgement;
use App\Models\Branch;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Bank lodgements')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $branch = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', BankLodgement::class);
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'status', 'branch'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'branch');
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $lodgements = BankLodgement::query()
            ->visibleTo($user)
            ->with(['bank', 'bankBranch', 'branch', 'creator', 'treasuryVerifiedBy'])
            ->withCount('items')
            ->search($this->search)
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->branch !== '', fn ($q) => $q->where('branch_id', $this->branch))
            ->latest('deposit_date')
            ->latest('id')
            ->paginate(20);

        return view('pages.lodgements.⚡index.index', [
            'lodgements' => $lodgements,
            'branches' => Branch::query()->whereIn('id', $user->accessibleBranchIds())->orderBy('name')->get(),
            'statuses' => LodgementStatus::cases(),
            'totals' => [
                'amount' => $lodgements->getCollection()->sum('amount'),
                'awaiting' => BankLodgement::query()->visibleTo($user)
                    ->where('status', LodgementStatus::UnderTreasuryReview->value)
                    ->count(),
            ],
        ]);
    }
};
