<?php

use App\Enums\ReconciliationStatus;
use App\Exceptions\FinancialRuleViolation;
use App\Models\Branch;
use App\Models\ReconciliationRun;
use App\Services\ReconciliationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Reconciliation')] class extends Component
{
    use WithPagination;

    public string $branch_id = '';

    public string $period_start = '';

    public string $period_end = '';

    public string $form_error = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', ReconciliationRun::class);

        $this->period_start = today()->startOfMonth()->toDateString();
        $this->period_end = today()->toDateString();
        $this->branch_id = (string) (auth()->user()->branch_id ?? '');
    }

    public function run(ReconciliationService $reconciliation): void
    {
        Gate::authorize('create', ReconciliationRun::class);

        $this->form_error = '';

        $this->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'period_start' => ['required', 'date', 'before_or_equal:today'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', 'before_or_equal:today'],
        ]);

        try {
            $run = $reconciliation->run(auth()->user(), [
                'branch_id' => (int) $this->branch_id,
                'period_start' => $this->period_start,
                'period_end' => $this->period_end,
            ]);
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->form_error = $exception->getMessage();

            return;
        }

        session()->flash('status', 'Reconciliation '.$run->reference.' completed: '.$run->matched_count.' matched, '.$run->unmatched_count.' unmatched.');
        $this->redirectRoute('reconciliation.show', $run, navigate: false);
    }

    public function render()
    {
        $runs = ReconciliationRun::query()
            ->with(['branch', 'creator'])
            ->when(! auth()->user()->canAccessAllBranches(), fn ($q) => $q->whereIn('branch_id', auth()->user()->accessibleBranchIds()))
            ->latest('created_at')
            ->paginate(15);

        return view('pages.reconciliation.⚡index.index', [
            'runs' => $runs,
            'branches' => Branch::query()->whereIn('id', auth()->user()->accessibleBranchIds())->orderBy('name')->get(),
            'statuses' => ReconciliationStatus::cases(),
            'stats' => [
                'completed' => ReconciliationRun::query()
                    ->when(! auth()->user()->canAccessAllBranches(), fn ($q) => $q->whereIn('branch_id', auth()->user()->accessibleBranchIds()))
                    ->where('status', ReconciliationStatus::Completed->value)
                    ->count(),
                'unmatched' => ReconciliationRun::query()
                    ->when(! auth()->user()->canAccessAllBranches(), fn ($q) => $q->whereIn('branch_id', auth()->user()->accessibleBranchIds()))
                    ->sum('unmatched_count'),
            ],
        ]);
    }
};
