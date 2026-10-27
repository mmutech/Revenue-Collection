<div class="space-y-6">
    <x-ui.page-header title="Reconciliation" subtitle="Match verified collections to treasury-confirmed bank deposits and surface every exception." />

    @if (session('status'))
        <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat label="Completed runs" :value="$stats['completed']" icon="refresh" color="mint" />
        <x-ui.stat label="Unmatched items (all runs)" :value="$stats['unmatched']" icon="alert" color="rose" />
        <x-ui.stat label="Run history" :value="$runs->total()" icon="document" color="navy" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Reconciliation runs" subtitle="Collections versus lodgements" padding="false">
                @if ($runs->isEmpty())
                    <x-ui.empty title="No reconciliation runs" message="Run a reconciliation for a closed period to test collections against treasury confirmations." icon="refresh" />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-5 py-3 text-left">Reference</th>
                                    <th class="px-4 py-3 text-left">Period</th>
                                    <th class="px-4 py-3 text-left">Branch</th>
                                    <th class="px-4 py-3 text-right">Matched</th>
                                    <th class="px-4 py-3 text-right">Unmatched</th>
                                    <th class="px-4 py-3 text-right">Variance</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach ($runs as $run)
                                    <tr class="transition hover:bg-slate-50/70">
                                        <td class="table-cell">
                                            <a href="{{ route('reconciliation.show', $run) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">{{ $run->reference }}</a>
                                            <span class="block text-[10px] text-slate-400">{{ $run->created_at?->format('j M Y H:i') }}</span>
                                        </td>
                                        <td class="table-cell text-xs">{{ $run->period_start?->format('j M') }} – {{ $run->period_end?->format('j M Y') }}</td>
                                        <td class="table-cell text-xs text-slate-500">{{ $run->branch?->code ?? 'All branches' }}</td>
                                        <td class="table-cell text-right text-xs font-semibold text-emerald-600">{{ $run->matched_count }}</td>
                                        <td class="table-cell text-right text-xs font-semibold text-rose-600">{{ $run->unmatched_count }}</td>
                                        <td class="table-cell text-right"><x-ui.money :amount="$run->total_variance" /></td>
                                        <td class="table-cell"><x-ui.status-badge :status="$run->status" /></td>
                                        <td class="table-cell text-right">
                                            <a href="{{ route('reconciliation.show', $run) }}" class="text-slate-400 hover:text-navy-700" aria-label="Open run">
                                                <x-icon name="chevron-right" class="h-4 w-4" />
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($runs->hasPages())
                        <div class="border-t border-slate-100 px-5 py-3">{{ $runs->links() }}</div>
                    @endif
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            @if (auth()->user()->can('reconciliation.run'))
                <x-ui.card title="Run reconciliation" subtitle="Executes synchronously against verified collections">
                    @if ($form_error)
                        <x-ui.alert variant="error" class="mb-3">{{ $form_error }}</x-ui.alert>
                    @endif
                    <form wire:submit="run" class="space-y-3">
                        <x-ui.select wire:model="branch_id" name="branch_id" label="Branch" required>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->code }} — {{ $branch->name }}</option>
                            @endforeach
                        </x-ui.select>
                        <div class="grid grid-cols-2 gap-2">
                            <x-ui.input wire:model="period_start" name="period_start" type="date" label="From" required />
                            <x-ui.input wire:model="period_end" name="period_end" type="date" label="To" required />
                        </div>
                        <x-ui.button type="submit" class="w-full" icon="refresh" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="run">Run reconciliation</span>
                            <span wire:loading wire:target="run">Reconciling…</span>
                        </x-ui.button>
                    </form>
                </x-ui.card>
            @else
                <x-ui.alert variant="info" title="Read-only access">
                    Only managers with the reconciliation permission can execute a run.
                </x-ui.alert>
            @endif

            <x-ui.card title="How matching works">
                <ul class="space-y-2.5 text-[11px] leading-relaxed text-slate-600">
                    <li>• A verified payment matches when its batch is fully allocated to lodgements confirmed by treasury.</li>
                    <li>• Unmatched payments raise a missing-transaction discrepancy unless one is already open.</li>
                    <li>• Confirmed lodgements are compared to their batch allocations; any difference raises an amount mismatch.</li>
                </ul>
            </x-ui.card>
        </div>
    </div>
</div>
