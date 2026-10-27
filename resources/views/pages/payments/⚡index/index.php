<?php

use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\PaymentChannel;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Payments')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $channel = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $branch = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Payment::class);

        $this->from = $this->from ?: today()->startOfMonth()->toDateString();
        $this->to = $this->to ?: today()->toDateString();
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'channel', 'status', 'branch', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'channel', 'status', 'branch');
        $this->from = today()->startOfMonth()->toDateString();
        $this->to = today()->toDateString();
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $payments = Payment::query()
            ->visibleTo($user)
            ->with(['channel', 'branch', 'cashier', 'receipt'])
            ->search($this->search)
            ->when($this->channel !== '', fn ($q) => $q->where('channel_id', $this->channel))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->branch !== '', fn ($q) => $q->where('branch_id', $this->branch))
            ->when($this->from !== '', fn ($q) => $q->whereDate('payment_date', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('payment_date', '<=', $this->to))
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20);

        $summary = (clone $payments)->getCollection();

        return view('pages.payments.⚡index.index', [
            'payments' => $payments,
            'channels' => PaymentChannel::query()->orderBy('sort_order')->get(),
            'branches' => Branch::query()->whereIn('id', $user->accessibleBranchIds())->orderBy('name')->get(),
            'statuses' => PaymentStatus::cases(),
            'totals' => [
                'amount' => $payments->getCollection()->sum('amount'),
                'count' => $payments->total(),
            ],
        ]);
    }
};
