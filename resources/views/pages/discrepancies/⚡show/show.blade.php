<div class="space-y-6">
    <x-ui.page-header
        :title="'Discrepancy '.$discrepancy->reference"
        subtitle="Exception details and resolution history."
        :back="route('discrepancies.index')"
    >
        <x-slot:actions>
            <x-ui.status-badge :status="$discrepancy->status" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card title="Discrepancy details" class="lg:col-span-2">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><dt class="text-xs text-slate-500">Type</dt><dd class="mt-1 text-sm font-medium">{{ $discrepancy->type?->value ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Amount</dt><dd class="mt-1 text-sm font-medium"><x-ui.money :amount="$discrepancy->amount" /></dd></div>
                <div><dt class="text-xs text-slate-500">Branch</dt><dd class="mt-1 text-sm font-medium">{{ $discrepancy->branch?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Created</dt><dd class="mt-1 text-sm font-medium">{{ $discrepancy->created_at?->format('j M Y H:i') ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Description</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $discrepancy->description }}</dd></div>
                @if ($discrepancy->resolution)
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Resolution</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $discrepancy->resolution }}</dd></div>
                @endif
            </dl>
        </x-ui.card>

        <x-ui.card title="Related records">
            <div class="space-y-3 text-sm">
                @if ($discrepancy->batch)
                    <a href="{{ route('collections.show', $discrepancy->batch) }}" class="block text-navy-700 hover:underline">Batch {{ $discrepancy->batch->batch_number }}</a>
                @endif
                @if ($discrepancy->payment)
                    <a href="{{ route('payments.show', $discrepancy->payment) }}" class="block text-navy-700 hover:underline">Payment {{ $discrepancy->payment->reference }}</a>
                @endif
                @if ($discrepancy->lodgement)
                    <a href="{{ route('lodgements.show', $discrepancy->lodgement) }}" class="block text-navy-700 hover:underline">Lodgement {{ $discrepancy->lodgement->lodgement_number }}</a>
                @endif
                @if (! $discrepancy->batch && ! $discrepancy->payment && ! $discrepancy->lodgement)
                    <p class="text-xs text-slate-500">No related transaction is linked.</p>
                @endif
            </div>
        </x-ui.card>
    </div>
</div>
