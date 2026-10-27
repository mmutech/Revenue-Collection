<div class="space-y-6">
    <x-ui.page-header title="Discrepancies" subtitle="Review collection and deposit exceptions within your branch scope." />

    <x-ui.card title="Discrepancy register" padding="false">
        <div class="border-b border-slate-100 p-4">
            <x-ui.input wire:model.live.debounce.300ms="search" name="search" label="Search" placeholder="Reference or description" />
        </div>

        @if ($discrepancies->isEmpty())
            <x-ui.empty title="No discrepancies found" message="Discrepancies will appear here when an exception is recorded." icon="alert" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="table-head">
                        <tr>
                            <th class="px-5 py-3 text-left">Reference</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Branch</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($discrepancies as $discrepancy)
                            <tr wire:key="discrepancy-{{ $discrepancy->id }}">
                                <td class="table-cell">
                                    <a href="{{ route('discrepancies.show', $discrepancy) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">
                                        {{ $discrepancy->reference }}
                                    </a>
                                    <span class="block max-w-sm truncate text-[11px] text-slate-500">{{ $discrepancy->description }}</span>
                                </td>
                                <td class="table-cell">{{ $discrepancy->type?->value ?? '—' }}</td>
                                <td class="table-cell">{{ $discrepancy->branch?->name ?? '—' }}</td>
                                <td class="table-cell text-right"><x-ui.money :amount="$discrepancy->amount" /></td>
                                <td class="table-cell"><x-ui.status-badge :status="$discrepancy->status" /></td>
                                <td class="table-cell">{{ $discrepancy->created_at?->format('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($discrepancies->hasPages())
                <div class="border-t border-slate-100 px-5 py-3">{{ $discrepancies->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
