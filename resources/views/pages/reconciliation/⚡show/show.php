<?php

use App\Enums\ReconciliationItemStatus;
use App\Models\ReconciliationItem;
use App\Models\ReconciliationRun;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Reconciliation detail')] class extends Component
{
    use WithPagination;

    public ReconciliationRun $run;

    #[Url]
    public string $item_status = '';

    #[Url]
    public string $type = '';

    public function mount(ReconciliationRun $run): void
    {
        Gate::authorize('view', $run);

        $this->run = $run->load(['branch', 'creator']);
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['item_status', 'type'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $items = ReconciliationItem::query()
            ->where('reconciliation_run_id', $this->run->id)
            ->with(['payment.channel', 'batch', 'lodgement.bank'])
            ->when($this->item_status !== '', fn ($q) => $q->where('status', $this->item_status))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->orderByRaw("CASE WHEN status = 'matched' THEN 1 ELSE 0 END")
            ->orderBy('id')
            ->paginate(50);

        $channelSummary = $this->run->summary['by_channel'] ?? [];

        return view('pages.reconciliation.⚡show.show', [
            'items' => $items,
            'statuses' => ReconciliationItemStatus::cases(),
            'types' => ReconciliationItem::query()
                ->where('reconciliation_run_id', $this->run->id)
                ->distinct()
                ->pluck('type'),
            'channelSummary' => $channelSummary,
        ]);
    }
};
