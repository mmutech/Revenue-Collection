<?php

use App\Enums\BatchStatus;
use App\Exceptions\FinancialRuleViolation;
use App\Models\Branch;
use App\Models\CollectionBatch;
use App\Services\CollectionBatchService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Daily collection batches')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $branch = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public bool $showCreate = false;

    public string $new_branch_id = '';

    public string $new_business_date = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', CollectionBatch::class);

        $this->new_branch_id = (string) (auth()->user()->branch_id ?? '');
        $this->new_business_date = today()->toDateString();
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'status', 'branch', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        Gate::authorize('create', CollectionBatch::class);
        $this->showCreate = true;
    }

    public function create(CollectionBatchService $batches)
    {
        Gate::authorize('create', CollectionBatch::class);

        $this->validate([
            'new_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'new_business_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        try {
            $batch = $batches->create(auth()->user(), [
                'branch_id' => (int) $this->new_branch_id,
                'business_date' => $this->new_business_date,
            ]);
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->addError('new_business_date', $exception->getMessage());

            return;
        }

        session()->flash('status', 'Batch '.$batch->batch_number.' created with '.$batch->item_count.' payments.');

        return $this->redirectRoute('collections.show', $batch, navigate: false);
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'branch', 'from', 'to');
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $batches = CollectionBatch::query()
            ->visibleTo($user)
            ->with(['cashier', 'branch', 'collectionPoint'])
            ->withSum(['lodgementItems as allocated_sum' => fn ($q) => $q->whereHas('lodgement', fn ($l) => $l->whereNotIn('status', ['returned', 'rejected']))], 'amount')
            ->search($this->search)
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->branch !== '', fn ($q) => $q->where('branch_id', $this->branch))
            ->when($this->from !== '', fn ($q) => $q->whereDate('business_date', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('business_date', '<=', $this->to))
            ->latest('business_date')
            ->latest('id')
            ->paginate(20);

        return view('pages.collections.⚡index.index', [
            'batches' => $batches,
            'branches' => Branch::query()->whereIn('id', $user->accessibleBranchIds())->orderBy('name')->get(),
            'statuses' => BatchStatus::cases(),
            'totals' => [
                'expected' => $batches->getCollection()->sum('expected_total'),
                'outstanding' => $batches->getCollection()->sum(
                    fn (CollectionBatch $batch) => (float) $batch->expected_total - (float) ($batch->allocated_sum ?? 0)
                ),
                'pending' => CollectionBatch::query()->visibleTo($user)
                    ->whereIn('status', [BatchStatus::Submitted->value, BatchStatus::PendingVerification->value])
                    ->count(),
            ],
        ]);
    }
};
