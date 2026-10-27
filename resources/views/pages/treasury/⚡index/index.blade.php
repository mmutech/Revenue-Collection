<div class="space-y-6">
    <x-ui.page-header title="Treasury workspace" subtitle="Confirm that approved lodgements have been credited to the organisation's bank account.">
        <x-slot:actions>
            <x-ui.button :href="route('lodgements.index')" variant="secondary" icon="bank">All lodgements</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat label="Awaiting confirmation" :value="$queue->count()" icon="shield" color="purple" />
        <x-ui.stat label="Value pending" :value="\App\Support\Money::compact($queueTotal)" icon="bank" color="amber" />
        <x-ui.stat label="Confirmed this month" :value="\App\Support\Money::compact($confirmedMonth)" icon="check-circle" color="mint" />
    </div>

    @unless (auth()->user()->can('treasury.confirm'))
        <x-ui.alert variant="info" title="Read-only treasury view">
            You can review deposits and decisions. Only treasury officers with the confirmation permission can record a decision.
        </x-ui.alert>
    @endunless

    <x-ui.card title="Awaiting treasury confirmation" subtitle="Oldest submissions first" padding="false">
        @if ($queue->isEmpty())
            <x-ui.empty title="No deposits awaiting review" message="Submitted lodgements will appear here for confirmation." icon="shield" />
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($queue as $lodgement)
                    <div class="flex flex-wrap items-center gap-4 px-5 py-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                            <x-icon name="bank" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('treasury.show', $lodgement) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">{{ $lodgement->lodgement_number }}</a>
                                <x-ui.status-badge :status="$lodgement->status" />
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">
                                {{ $lodgement->bank?->short_name ?? $lodgement->bank?->name }} · slip {{ $lodgement->deposit_slip_number }} · {{ $lodgement->branch?->name }}
                            </p>
                            <p class="text-[11px] text-slate-400">
                                Submitted {{ $lodgement->submitted_at?->diffForHumans() }} by {{ $lodgement->submittedBy?->name ?? $lodgement->creator?->name }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="tabular text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($lodgement->amount) }}</p>
                            <p class="text-[10px] uppercase tracking-wider text-slate-400">deposited</p>
                        </div>
                        <x-ui.button :href="route('treasury.show', $lodgement)" size="sm" icon="shield">Review</x-ui.button>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Recently confirmed" subtitle="Deposits recognised in the treasury ledger" padding="false">
        @if ($confirmed->isEmpty())
            <x-ui.empty title="No confirmed deposits yet" message="Treasury-confirmed lodgements will be listed here." icon="check-circle" />
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($confirmed as $lodgement)
                    <a href="{{ route('lodgements.show', $lodgement) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-slate-50">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <x-icon name="check-circle" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-xs font-semibold text-slate-800">{{ $lodgement->lodgement_number }}</p>
                            <p class="text-[11px] text-slate-500">
                                {{ $lodgement->bank?->short_name ?? $lodgement->bank?->name }} · confirmed by {{ $lodgement->treasuryVerifiedBy?->name }}
                            </p>
                        </div>
                        <x-ui.money :amount="$lodgement->amount" class="text-xs font-semibold" />
                        <span class="text-[11px] text-slate-400">{{ $lodgement->treasury_verified_at?->format('j M') }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-ui.card>
</div>
