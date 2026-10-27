<?php

use App\Enums\BatchStatus;
use App\Exceptions\FinancialRuleViolation;
use App\Models\Bank;
use App\Models\BankBranch;
use App\Models\BankLodgement;
use App\Models\Branch;
use App\Models\CollectionBatch;
use App\Services\LodgementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('New bank lodgement')] class extends Component
{
    public string $branch_id = '';

    public string $bank_id = '';

    public string $bank_branch_id = '';

    public string $deposit_slip_number = '';

    public string $deposit_date = '';

    public string $deposited_by_name = '';

    public string $notes = '';

    /** @var array<int, string> */
    public array $allocations = [];

    public string $form_error = '';

    public function mount(): void
    {
        Gate::authorize('create', BankLodgement::class);

        $user = auth()->user();
        $this->branch_id = (string) ($user->branch_id ?? '');
        $this->deposit_date = today()->toDateString();
        $this->deposited_by_name = $user->name;

        foreach ($this->eligibleBatches() as $batch) {
            $this->allocations[$batch->id] = number_format((float) $batch->available_amount, 2, '.', '');
        }
    }

    #[Computed]
    public function bankBranches()
    {
        return $this->bank_id !== ''
            ? BankBranch::query()->where('bank_id', $this->bank_id)->where('is_active', true)->orderBy('name')->get()
            : collect();
    }

    /**
     * @return Collection<int, CollectionBatch>
     */
    private function eligibleBatches()
    {
        return CollectionBatch::query()
            ->visibleTo(auth()->user())
            ->with(['cashier', 'branch'])
            ->withSum(['lodgementItems as allocated_sum' => fn ($q) => $q->whereHas('lodgement', fn ($l) => $l->whereNotIn('status', ['returned', 'rejected']))], 'amount')
            ->whereIn('status', [BatchStatus::ApprovedForLodgement->value, BatchStatus::PartiallyLodged->value])
            ->when($this->branch_id !== '', fn ($q) => $q->where('branch_id', $this->branch_id))
            ->orderBy('business_date')
            ->get()
            ->map(function (CollectionBatch $batch) {
                $batch->available_amount = max(0, (float) $batch->expected_total - (float) ($batch->allocated_sum ?? 0));

                return $batch;
            })
            ->filter(fn (CollectionBatch $batch) => $batch->available_amount > 0)
            ->values();
    }

    public function save(LodgementService $lodgements)
    {
        $this->form_error = '';

        $this->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'bank_id' => ['required', 'integer', 'exists:banks,id'],
            'bank_branch_id' => ['nullable', 'integer', 'exists:bank_branches,id'],
            'deposit_slip_number' => ['required', 'string', 'max:80'],
            'deposit_date' => ['required', 'date', 'before_or_equal:today'],
            'deposited_by_name' => ['required', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'allocations' => ['required', 'array'],
        ]);

        $payload = [];
        foreach ($this->allocations as $batchId => $amount) {
            if ($amount === '' || (float) $amount <= 0) {
                continue;
            }

            $payload[] = ['batch_id' => (int) $batchId, 'amount' => $amount];
        }

        if ($payload === []) {
            $this->addError('allocations', 'Enter an amount for at least one batch.');

            return null;
        }

        try {
            $lodgement = $lodgements->create(auth()->user(), [
                'branch_id' => (int) $this->branch_id,
                'bank_id' => (int) $this->bank_id,
                'bank_branch_id' => $this->bank_branch_id !== '' ? (int) $this->bank_branch_id : null,
                'deposit_slip_number' => $this->deposit_slip_number,
                'deposit_date' => $this->deposit_date,
                'deposited_by_name' => $this->deposited_by_name,
                'notes' => $this->notes ?: null,
            ], $payload);
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->form_error = $exception->getMessage();

            return null;
        }

        session()->flash('status', 'Lodgement '.$lodgement->lodgement_number.' created. Attach the deposit slip before submitting.');

        return $this->redirectRoute('lodgements.show', $lodgement, navigate: false);
    }

    public function render()
    {
        $batches = $this->eligibleBatches();
        $selectedTotal = collect($this->allocations)->sum(fn ($amount) => (float) $amount);

        return view('pages.lodgements.⚡create.create', [
            'branches' => Branch::query()->whereIn('id', auth()->user()->accessibleBranchIds())->orderBy('name')->get(),
            'banks' => Bank::query()->where('is_active', true)->orderBy('name')->get(),
            'batches' => $batches,
            'selectedTotal' => $selectedTotal,
        ]);
    }
};
