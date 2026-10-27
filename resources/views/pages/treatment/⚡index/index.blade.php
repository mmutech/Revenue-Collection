<div class="space-y-6">
    <x-ui.page-header title="Treatment requests" subtitle="Review account treatment requests within your branch scope.">
        <x-slot:actions>
            @can('treatment.create')
                <x-ui.button :href="route('treatment.create')" icon="plus">New request</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card title="Request register" padding="false">
        <div class="border-b border-slate-100 p-4">
            <x-ui.input wire:model.live.debounce.300ms="search" name="search" label="Search" placeholder="Reference, account, or description" />
        </div>

        @if ($requests->isEmpty())
            <x-ui.empty title="No treatment requests found" message="Requests will appear here when submitted." icon="adjustments" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="table-head">
                        <tr>
                            <th class="px-5 py-3 text-left">Reference</th>
                            <th class="px-4 py-3 text-left">Account</th>
                            <th class="px-4 py-3 text-left">Treatment</th>
                            <th class="px-4 py-3 text-left">Branch</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Requested</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($requests as $treatment)
                            <tr wire:key="treatment-{{ $treatment->id }}">
                                <td class="table-cell">
                                    <a href="{{ route('treatment.show', $treatment) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">
                                        {{ $treatment->reference }}
                                    </a>
                                    <span class="block max-w-sm truncate text-[11px] text-slate-500">{{ $treatment->description }}</span>
                                </td>
                                <td class="table-cell">{{ $treatment->account?->account_number ?? $treatment->account?->name ?? '—' }}</td>
                                <td class="table-cell">{{ $treatment->type?->name ?? '—' }}</td>
                                <td class="table-cell">{{ $treatment->branch?->name ?? '—' }}</td>
                                <td class="table-cell"><x-ui.status-badge :status="$treatment->status" /></td>
                                <td class="table-cell">{{ $treatment->created_at?->format('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($requests->hasPages())
                <div class="border-t border-slate-100 px-5 py-3">{{ $requests->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
