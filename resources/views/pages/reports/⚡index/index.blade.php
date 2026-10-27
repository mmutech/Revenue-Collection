<div class="space-y-6">
    <x-ui.page-header title="Reports" subtitle="Review operational reporting workspaces and generated report exports." />

    <x-ui.card title="Report generation">
        <p class="text-sm text-slate-600">
            Report definitions and queued report generation are not configured yet. You can review current operational records in the available workspaces below.
        </p>

        <div class="mt-4 flex flex-wrap gap-2">
            @can('payments.view')
                <x-ui.button :href="route('payments.index')" variant="secondary">Payment register</x-ui.button>
            @endcan
            @can('batches.view')
                <x-ui.button :href="route('collections.index')" variant="secondary">Collection batches</x-ui.button>
            @endcan
            @can('lodgements.view')
                <x-ui.button :href="route('lodgements.index')" variant="secondary">Bank lodgements</x-ui.button>
            @endcan
            @can('reconciliation.view')
                <x-ui.button :href="route('reconciliation.index')" variant="secondary">Reconciliation</x-ui.button>
            @endcan
            @can('reports.export')
                <x-ui.button :href="route('reports.exports')" variant="secondary" icon="download">Report exports</x-ui.button>
            @endcan
        </div>
    </x-ui.card>
</div>
