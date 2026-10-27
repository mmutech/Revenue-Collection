@php($run = $this->run)
<div class="space-y-6">
    <x-ui.page-header title="Reconciliation {{ $run->reference }}" subtitle="{{ $run->branch?->name ?? 'All branches' }} · {{ $run->period_start?->format('j M Y') }} to {{ $run->period_end?->format('j M Y') }}" back="{{ route('reconciliation.index') }}">
        <x-slot:actions>
            <x-ui.status-badge :status="$run->status" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($run->error)
        <x-ui.alert variant="error" title="Run failed">{{ $run->error }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Expected collections" :value="\App\Support\Money::compact($run->total_expected)" icon="banknotes" color="navy" />
        <x-ui.stat label="Matched to treasury" :value="\App\Support\Money::compact($run->total_actual)" icon="check-circle" color="mint" />
        <x-ui.stat label="Variance" :value="\App\Support\Money::compact($run->total_variance)" icon="alert" :color="abs((float) $run->total_variance) < 0.01 ? 'mint' : 'rose'" />
        <x-ui.stat label="Items" :value="$run->matched_count.' / '.$run->unmatched_count" hint="matched / unmatched" icon="scale" color="amber" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
        <div class="xl:col-span-3">
            <x-ui.card title="Reconciliation items" subtitle="Payments and lodgements examined in this run" padding="false">
                <div class="border-b border-slate-100 p-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <x-ui.select wire:model.live="item_status" name="item_status" placeholder="All statuses">
                            @foreach ($statuses as $statusOption)
                                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.select wire:model.live="type" name="type" placeholder="All item types">
                            @foreach ($types as $typeOption)
                                <option value="{{ $typeOption }}">{{ str($typeOption)->headline() }}</option>
                            @endforeach
                        </x-ui.select>
                        <div class="flex items-center text-[11px] text-slate-500">{{ $items->total() }} items</div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3 text-left">Item</th>
                                <th class="px-4 py-3 text-left">Reference</th>
                                <th class="px-4 py-3 text-left">Batch / bank</th>
                                <th class="px-4 py-3 text-right">Expected</th>
                                <th class="px-4 py-3 text-right">Actual</th>
                                <th class="px-4 py-3 text-right">Variance</th>
                                <th class="px-4 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($items as $item)
                                <tr class="transition hover:bg-slate-50/60">
                                    <td class="table-cell text-xs font-medium text-slate-700">{{ str($item->type)->headline() }}</td>
                                    <td class="table-cell">
                                        @if ($item->payment)
                                            <a href="{{ route('payments.show', $item->payment) }}" class="font-mono text-[11px] font-semibold text-navy-700 hover:text-navy-900">{{ $item->payment->reference }}</a>
                                            <span class="block text-[10px] text-slate-400">{{ $item->payment->channel?->name }}</span>
                                        @elseif ($item->lodgement)
                                            <a href="{{ route('lodgements.show', $item->lodgement) }}" class="font-mono text-[11px] font-semibold text-navy-700 hover:text-navy-900">{{ $item->lodgement->lodgement_number }}</a>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-[11px] text-slate-500">
                                        {{ $item->batch?->batch_number ?? $item->lodgement?->bank?->short_name ?? '—' }}
                                    </td>
                                    <td class="table-cell text-right"><x-ui.money :amount="$item->expected_amount" /></td>
                                    <td class="table-cell text-right"><x-ui.money :amount="$item->actual_amount" /></td>
                                    <td class="table-cell text-right">
                                        <span @class(['tabular font-semibold', 'text-rose-600' => (float) $item->variance < -0.001, 'text-amber-600' => (float) $item->variance > 0.001, 'text-slate-400' => abs((float) $item->variance) < 0.001])>
                                            {{ \App\Support\Money::format($item->variance) }}
                                        </span>
                                    </td>
                                    <td class="table-cell"><x-ui.status-badge :status="$item->status" /></td>
                                </tr>
                                @if ($item->notes)
                                    <tr class="bg-slate-50/40">
                                        <td colspan="7" class="px-5 pb-2 text-[10px] text-slate-500">{{ $item->notes }}</td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7"><x-ui.empty title="No items" message="This run examined no records for the selected filters." icon="refresh" /></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($items->hasPages())
                    <div class="border-t border-slate-100 px-5 py-3">{{ $items->links() }}</div>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Run metadata">
                <dl class="space-y-3 text-xs">
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Type</dt>
                        <dd class="text-right font-medium text-slate-800">{{ str($run->type)->headline() }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Run by</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $run->creator?->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Started</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $run->started_at?->format('j M Y H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Completed</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $run->completed_at?->format('j M Y H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Payments examined</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $run->summary['payments_examined'] ?? '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            @if ($channelSummary !== [])
                <x-ui.card title="Channel mix" subtitle="Examined value by channel">
                    <dl class="space-y-2.5 text-xs">
                        @foreach ($channelSummary as $channel => $amount)
                            <div class="flex items-center justify-between gap-2">
                                <dt class="capitalize text-slate-500">{{ str($channel)->replace('_', ' ') }}</dt>
                                <dd class="tabular font-semibold text-slate-800">{{ \App\Support\Money::format($amount) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.card>
            @endif

            <x-ui.alert variant="info" title="Discrepancies">
                Exceptions in this run are recorded against the relevant payment or lodgement.
                <a href="{{ route('discrepancies.index') }}" class="font-semibold underline decoration-blue-300 underline-offset-2">Review discrepancies</a>.
            </x-ui.alert>
        </div>
    </div>
</div>
