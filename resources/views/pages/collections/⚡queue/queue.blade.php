<div class="space-y-6">
    <x-ui.page-header title="Clearing queue" subtitle="Verify submitted cashier collections against the physical count before lodgement." back="{{ route('collections.index') }}">
        <x-slot:actions>
            <x-ui.button :href="route('collections.index')" variant="secondary" icon="clipboard">All batches</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat label="Awaiting verification" :value="$queue->count()" icon="scale" color="amber" />
        <x-ui.stat label="Value in queue" :value="\App\Support\Money::compact($queueTotal)" icon="banknotes" color="navy" />
        <x-ui.stat label="Returned for correction" :value="$returned->count()" icon="refresh" color="rose" />
    </div>

    @if (! auth()->user()->can('batches.verify'))
        <x-ui.alert variant="info" title="Read-only view">
            You can monitor the clearing queue, but only clearing officers with the verify permission can record a decision.
        </x-ui.alert>
    @endif

    <x-ui.card title="Pending verification" subtitle="Oldest submissions first" padding="false">
        @if ($queue->isEmpty())
            <x-ui.empty title="Queue is clear" message="No collection batches are awaiting clearing verification." icon="check-circle" />
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($queue as $batch)
                    <div class="flex flex-wrap items-center gap-4 px-5 py-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <x-icon name="clipboard" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('collections.show', $batch) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">{{ $batch->batch_number }}</a>
                                <x-ui.status-badge :status="$batch->status" />
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">
                                {{ $batch->cashier?->name }} · {{ $batch->branch?->name }} · {{ $batch->item_count }} payments
                            </p>
                            <p class="text-[11px] text-slate-400">
                                Submitted {{ $batch->submitted_at?->diffForHumans() }} · declared {{ \App\Support\Money::format($batch->declared_total ?? $batch->expected_total) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="tabular text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($batch->expected_total) }}</p>
                            <p class="text-[10px] uppercase tracking-wider text-slate-400">expected</p>
                        </div>
                        <x-ui.button :href="route('collections.show', $batch)" size="sm" variant="secondary" icon="eye">Review</x-ui.button>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    @if ($returned->isNotEmpty())
        <x-ui.card title="Recently returned" subtitle="Batches sent back to the cashier for correction" padding="false">
            <div class="divide-y divide-slate-100">
                @foreach ($returned as $batch)
                    <a href="{{ route('collections.show', $batch) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-slate-50">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                            <x-icon name="refresh" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-xs font-semibold text-slate-800">{{ $batch->batch_number }}</p>
                            <p class="truncate text-[11px] text-slate-500">{{ $batch->return_reason }}</p>
                        </div>
                        <span class="text-[11px] text-slate-400">{{ $batch->returned_at?->diffForHumans() }}</span>
                    </a>
                @endforeach
            </div>
        </x-ui.card>
    @endif
</div>
