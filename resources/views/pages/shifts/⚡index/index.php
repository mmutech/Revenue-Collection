<?php

use App\Exceptions\FinancialRuleViolation;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\CollectionPoint;
use App\Services\CashierShiftService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Cashier shifts')] class extends Component
{
    use WithPagination;

    public string $branch_id = '';

    public string $collection_point_id = '';

    public string $opening_float = '0.00';

    public string $notes = '';

    public ?int $closingId = null;

    public string $counted_cash = '';

    public string $close_notes = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', CashierShift::class);

        $this->branch_id = (string) (auth()->user()->branch_id ?? '');
        $this->collection_point_id = (string) (auth()->user()->collection_point_id ?? '');
    }

    public function open(CashierShiftService $shifts): void
    {
        $this->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'collection_point_id' => ['nullable', 'integer', 'exists:collection_points,id'],
            'opening_float' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->collection_point_id !== '') {
            $point = CollectionPoint::query()->find($this->collection_point_id);

            if (! $point || (int) $point->branch_id !== (int) $this->branch_id) {
                $this->addError('collection_point_id', 'The collection point does not belong to the selected branch.');

                return;
            }
        }

        try {
            $shift = $shifts->open(auth()->user(), [
                'branch_id' => (int) $this->branch_id,
                'collection_point_id' => $this->collection_point_id !== '' ? (int) $this->collection_point_id : null,
                'opening_float' => $this->opening_float,
                'notes' => $this->notes ?: null,
            ]);
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->addError('opening_float', $exception->getMessage());

            return;
        }

        $this->reset('opening_float', 'notes');
        $this->opening_float = '0.00';
        $this->dispatch('toast', message: 'Shift opened. Cash collections can now be recorded.', type: 'success');

        unset($shift);
    }

    public function startClose(int $shiftId): void
    {
        $shift = CashierShift::query()->findOrFail($shiftId);
        Gate::authorize('close', $shift);

        $this->closingId = $shift->id;
        $this->counted_cash = '';
        $this->close_notes = '';
    }

    public function cancelClose(): void
    {
        $this->reset('closingId', 'counted_cash', 'close_notes');
    }

    public function close(CashierShiftService $shifts): void
    {
        $this->validate([
            'closingId' => ['required', 'integer'],
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'close_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shift = CashierShift::query()->findOrFail($this->closingId);

        try {
            $closed = $shifts->close($shift, auth()->user(), $this->counted_cash, $this->close_notes ?: null);
        } catch (FinancialRuleViolation|AuthorizationException|ValidationException $exception) {
            $this->addError('counted_cash', $exception->getMessage());

            return;
        }

        $variance = (float) $closed->variance;
        $this->reset('closingId', 'counted_cash', 'close_notes');
        $this->dispatch(
            'toast',
            message: abs($variance) < 0.01
                ? 'Shift closed and balanced.'
                : 'Shift closed with a variance of '.number_format($variance, 2).'. A discrepancy was raised.',
            type: abs($variance) < 0.01 ? 'success' : 'warning'
        );
    }

    public function render()
    {
        $user = auth()->user();
        $branchIds = $user->accessibleBranchIds();

        $points = CollectionPoint::query()
            ->whereIn('branch_id', $branchIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $openShift = CashierShift::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        $shifts = CashierShift::query()
            ->visibleTo($user)
            ->with(['branch', 'collectionPoint', 'closedBy'])
            ->when($user->isCashier(), fn ($q) => $q->where('user_id', $user->id))
            ->latest('opened_at')
            ->paginate(12);

        $shiftSummary = $openShift ? app(CashierShiftService::class)->summary($openShift) : null;

        return view('pages.shifts.⚡index.index', [
            'branches' => Branch::query()->whereIn('id', $branchIds)->orderBy('name')->get(),
            'points' => $points,
            'openShift' => $openShift,
            'shiftSummary' => $shiftSummary,
            'shifts' => $shifts,
        ]);
    }
};
