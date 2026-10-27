<?php

use App\Enums\LodgementStatus;
use App\Models\BankLodgement;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('Treasury workspace')] class extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', BankLodgement::class);
        abort_unless(auth()->user()->can('treasury.view'), 403);
    }

    public function render()
    {
        $user = auth()->user();

        $queue = BankLodgement::query()
            ->visibleTo($user)
            ->with(['bank', 'branch', 'creator', 'submittedBy'])
            ->where('status', LodgementStatus::UnderTreasuryReview->value)
            ->orderBy('submitted_at')
            ->get();

        $confirmed = BankLodgement::query()
            ->visibleTo($user)
            ->with(['bank', 'branch', 'treasuryVerifiedBy'])
            ->whereIn('status', [
                LodgementStatus::Verified->value,
                LodgementStatus::Reconciled->value,
                LodgementStatus::Closed->value,
            ])
            ->latest('treasury_verified_at')
            ->limit(10)
            ->get();

        $confirmedMonth = BankLodgement::query()
            ->visibleTo($user)
            ->whereIn('status', [
                LodgementStatus::Verified->value,
                LodgementStatus::Reconciled->value,
                LodgementStatus::Closed->value,
            ])
            ->whereBetween('treasury_verified_at', [now()->startOfMonth(), now()])
            ->sum('amount');

        return view('pages.treasury.⚡index.index', [
            'queue' => $queue,
            'confirmed' => $confirmed,
            'queueTotal' => $queue->sum('amount'),
            'confirmedMonth' => $confirmedMonth,
        ]);
    }
};
