<?php

use App\Enums\BatchStatus;
use App\Models\CollectionBatch;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('Clearing queue')] class extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', CollectionBatch::class);
    }

    public function render()
    {
        $user = auth()->user();

        $queue = CollectionBatch::query()
            ->visibleTo($user)
            ->with(['cashier', 'branch', 'collectionPoint'])
            ->whereIn('status', [BatchStatus::Submitted->value, BatchStatus::PendingVerification->value])
            ->orderBy('submitted_at')
            ->get();

        $returned = CollectionBatch::query()
            ->visibleTo($user)
            ->with(['cashier', 'branch'])
            ->where('status', BatchStatus::ReturnedForCorrection->value)
            ->latest('returned_at')
            ->limit(10)
            ->get();

        return view('pages.collections.⚡queue.queue', [
            'queue' => $queue,
            'returned' => $returned,
            'queueTotal' => $queue->sum('expected_total'),
        ]);
    }
};
