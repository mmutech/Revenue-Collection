<div class="space-y-6">
    <x-ui.page-header :title="$section['title']" :subtitle="$section['description']" />

    <x-ui.alert variant="info">
        These administration pages are currently read-only. Changes must be made through the existing seeder or deployment process.
    </x-ui.alert>

    <x-ui.card :title="$section['title']" padding="false">
        @if ($records->isEmpty())
            <x-ui.empty title="No records configured" message="There are no records available in this section." icon="document" />
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($records as $record)
                    <div wire:key="admin-record-{{ $loop->index }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $record['label'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $record['detail'] }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">{{ $record['status'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>
</div>
