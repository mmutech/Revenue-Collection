<?php

use App\Models\Account;
use App\Models\AccountTreatmentRequest;
use App\Models\AccountTreatmentType;
use App\Services\AccountTreatmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('Create treatment request')] class extends Component
{
    public string $account_id = '';

    public string $account_treatment_type_id = '';

    public string $account_treatment_reason_id = '';

    public string $amount = '';

    public string $effective_date = '';

    public string $description = '';

    public function mount(): void
    {
        Gate::authorize('create', AccountTreatmentRequest::class);
    }

    public function updated(string $property): void
    {
        if ($property === 'account_treatment_type_id') {
            $this->account_treatment_reason_id = '';
        }
    }

    public function save(AccountTreatmentService $treatmentService): void
    {
        Gate::authorize('create', AccountTreatmentRequest::class);

        $validated = $this->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'account_treatment_type_id' => ['required', 'integer', 'exists:account_treatment_types,id'],
            'account_treatment_reason_id' => ['nullable', 'integer', 'exists:account_treatment_reasons,id'],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999999999999.99'],
            'effective_date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:2000'],
        ]);
        $validated['account_treatment_reason_id'] = $validated['account_treatment_reason_id'] ?: null;
        $validated['amount'] = $validated['amount'] !== '' ? $validated['amount'] : null;
        $validated['effective_date'] = $validated['effective_date'] !== '' ? $validated['effective_date'] : null;

        $request = $treatmentService->create(auth()->user(), $validated);

        session()->flash('status', 'Treatment request '.$request->reference.' created successfully.');

        $this->redirectRoute('treatment.show', $request, navigate: false);
    }

    public function render(): View
    {
        $user = auth()->user();

        $accounts = Account::query()
            ->when(! $user->canAccessAllBranches(), function ($query) use ($user): void {
                $query->where(function ($branchQuery) use ($user): void {
                    $branchQuery->whereIn('branch_id', $user->accessibleBranchIds());

                    if ($user->branch_id) {
                        $branchQuery->orWhereNull('branch_id');
                    }
                });
            })
            ->orderBy('account_number')
            ->get();

        $types = AccountTreatmentType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $reasons = $this->account_treatment_type_id !== ''
            ? AccountTreatmentType::query()
                ->whereKey($this->account_treatment_type_id)
                ->first()
                ?->reasons()
                ->where('is_active', true)
                ->orderBy('name')
                ->get() ?? collect()
            : collect();

        return view('pages.treatment.⚡create.create', [
            'accounts' => $accounts,
            'types' => $types,
            'reasons' => $reasons,
        ]);
    }
};
