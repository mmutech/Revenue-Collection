<div class="space-y-6">
    <x-ui.page-header title="Audit trail" subtitle="Review immutable system activity and financial record changes." />

    <x-ui.card title="Recent activity" padding="false">
        @if ($entries->isEmpty())
            <x-ui.empty title="No audit entries" message="Recorded activity will appear here." icon="search" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="table-head">
                        <tr>
                            <th class="px-5 py-3 text-left">Event</th>
                            <th class="px-4 py-3 text-left">Actor</th>
                            <th class="px-4 py-3 text-left">Record</th>
                            <th class="px-4 py-3 text-left">Tags</th>
                            <th class="px-4 py-3 text-left">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($entries as $entry)
                            <tr wire:key="audit-entry-{{ $entry->id }}">
                                <td class="table-cell font-medium">{{ str($entry->event)->replace(['.', '_'], ' ')->headline() }}</td>
                                <td class="table-cell">{{ $entry->user?->name ?? 'System' }}</td>
                                <td class="table-cell text-xs">{{ $entry->auditable_type ? class_basename($entry->auditable_type).' #'.$entry->auditable_id : '—' }}</td>
                                <td class="table-cell text-xs">{{ $entry->tags ?? '—' }}</td>
                                <td class="table-cell">{{ $entry->created_at?->format('j M Y H:i:s') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($entries->hasPages())
                <div class="border-t border-slate-100 px-5 py-3">{{ $entries->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
