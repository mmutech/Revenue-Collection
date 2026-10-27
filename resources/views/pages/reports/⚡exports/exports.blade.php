<div class="space-y-6">
    <x-ui.page-header title="Report exports" subtitle="Review the status of reports generated for your account." />

    <x-ui.card title="Export history" padding="false">
        @if ($exports->isEmpty())
            <x-ui.empty title="No report exports" message="Generated report exports will appear here." icon="document" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="table-head">
                        <tr>
                            <th class="px-5 py-3 text-left">Report</th>
                            <th class="px-4 py-3 text-left">Format</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Requested by</th>
                            <th class="px-4 py-3 text-left">Requested</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($exports as $export)
                            <tr>
                                <td class="table-cell font-medium">{{ str($export->report_key)->replace('_', ' ')->title() }}</td>
                                <td class="table-cell uppercase">{{ $export->format }}</td>
                                <td class="table-cell">{{ str($export->status)->headline() }}</td>
                                <td class="table-cell">{{ $export->user?->name ?? 'Unknown user' }}</td>
                                <td class="table-cell">{{ $export->created_at?->format('j M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($exports->hasPages())
                <div class="border-t border-slate-100 px-5 py-3">{{ $exports->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
