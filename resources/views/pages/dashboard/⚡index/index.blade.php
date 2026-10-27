<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-mint-700">Control centre</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">
                Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ str(auth()->user()->name)->before(' ') }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">Here is the revenue position for your scope today.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('payments.create')
                <x-ui.button :href="route('payments.create')" icon="plus">Record payment</x-ui.button>
            @endcan
            @can('batches.submit')
                <x-ui.button :href="route('collections.index')" variant="secondary" icon="clipboard">Daily batches</x-ui.button>
            @endcan
            @can('reports.view')
                <x-ui.button :href="route('reports.index')" variant="secondary" icon="chart">Reports</x-ui.button>
            @endcan
        </div>
    </div>

    @if ($openShift)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-mint-200 bg-mint-50/70 px-4 py-3">
            <div class="flex items-center gap-3">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-mint-500 opacity-60"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-mint-600"></span>
                </span>
                <p class="text-xs font-semibold text-mint-900">
                    Shift open · {{ $openShift->collectionPoint?->name ?? 'Cash desk' }} · float {{ \App\Support\Money::format($openShift->opening_float) }}
                </p>
            </div>
            <a href="{{ route('shifts.index') }}" class="text-xs font-semibold text-mint-800 underline decoration-mint-300 underline-offset-2 hover:text-mint-900">
                Manage shift
            </a>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat
            label="Collected today"
            :value="\App\Support\Money::compact($todayTotal)"
            :hint="$todayCount.' transactions captured'"
            icon="banknotes"
            color="mint"
        />
        <x-ui.stat
            label="Month to date"
            :value="\App\Support\Money::compact($mtdTotal)"
            :hint="$mtdCount.' transactions this month'"
            icon="trending-up"
            color="navy"
        />
        <x-ui.stat
            label="Awaiting lodgement"
            :value="\App\Support\Money::compact($outstandingBatches)"
            :hint="$pendingVerification.' batches pending clearing'"
            icon="bank"
            color="amber"
        />
        <x-ui.stat
            label="Open exceptions"
            :value="$openDiscrepancies"
            :hint="$awaitingTreasury.' lodgements awaiting treasury'"
            icon="alert"
            color="rose"
        />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <x-ui.card title="Collection trend" subtitle="Daily collections over the last 14 days" padding="false">
                <div class="px-4 pb-4 pt-2">
                    <div class="h-64">
                        <canvas id="collections-trend" x-init="$nextTick(() => window.renderRevenueChart('collections-trend', @js($chart)))"></canvas>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <x-ui.card title="Channel mix" subtitle="Month to date by payment channel" padding="false">
            <div class="p-4">
                @if (collect($mixChart['data']['datasets'][0]['data'])->sum() > 0)
                    <div class="h-64">
                        <canvas id="channel-mix" x-init="$nextTick(() => window.renderRevenueChart('channel-mix', @js($mixChart)))"></canvas>
                    </div>
                @else
                    <x-ui.empty title="No collections yet" message="Channel distribution appears once payments are recorded this month." icon="chart" />
                @endif
            </div>
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        @if ($verificationQueue->isNotEmpty())
            <x-ui.card title="Clearing queue" subtitle="Submitted batches awaiting verification" padding="false">
                <div class="divide-y divide-slate-100">
                    @foreach ($verificationQueue as $batch)
                        <a href="{{ route('collections.show', $batch) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-slate-50">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                <x-icon name="scale" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-semibold text-slate-800">{{ $batch->batch_number }}</p>
                                <p class="truncate text-[11px] text-slate-500">{{ $batch->cashier?->name }} · {{ $batch->branch?->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="tabular text-xs font-semibold text-slate-900">{{ \App\Support\Money::format($batch->expected_total) }}</p>
                                <p class="text-[10px] text-slate-400">{{ $batch->submitted_at?->diffForHumans() }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="border-t border-slate-100 px-5 py-3">
                    <a href="{{ route('collections.queue') }}" class="text-xs font-semibold text-navy-700 hover:text-navy-900">Open clearing queue →</a>
                </div>
            </x-ui.card>
        @endif

        @if ($treasuryQueue->isNotEmpty())
            <x-ui.card title="Treasury review" subtitle="Lodgements awaiting confirmation" padding="false">
                <div class="divide-y divide-slate-100">
                    @foreach ($treasuryQueue as $lodgement)
                        <a href="{{ route('treasury.show', $lodgement) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-slate-50">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                                <x-icon name="shield" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-semibold text-slate-800">{{ $lodgement->lodgement_number }}</p>
                                <p class="truncate text-[11px] text-slate-500">{{ $lodgement->bank?->short_name ?? $lodgement->bank?->name }} · slip {{ $lodgement->deposit_slip_number }}</p>
                            </div>
                            <div class="text-right">
                                <p class="tabular text-xs font-semibold text-slate-900">{{ \App\Support\Money::format($lodgement->amount) }}</p>
                                <p class="text-[10px] text-slate-400">{{ $lodgement->submitted_at?->diffForHumans() }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="border-t border-slate-100 px-5 py-3">
                    <a href="{{ route('treasury.index') }}" class="text-xs font-semibold text-navy-700 hover:text-navy-900">Open treasury workspace →</a>
                </div>
            </x-ui.card>
        @endif

        @if ($treatmentQueue->isNotEmpty())
            <x-ui.card title="Treatment approvals" subtitle="Requests under review" padding="false">
                <div class="divide-y divide-slate-100">
                    @foreach ($treatmentQueue as $request)
                        <a href="{{ route('treatment.show', $request) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-slate-50">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                                <x-icon name="adjustments" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-semibold text-slate-800">{{ $request->reference }} · {{ $request->type?->name }}</p>
                                <p class="truncate text-[11px] text-slate-500">{{ $request->account?->name }} · stage {{ $request->current_stage }}</p>
                            </div>
                            <div class="text-right">
                                <p class="tabular text-xs font-semibold text-slate-900">{{ \App\Support\Money::format($request->amount) }}</p>
                                <p class="text-[10px] text-slate-400">{{ $request->submitted_at?->diffForHumans() }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="border-t border-slate-100 px-5 py-3">
                    <a href="{{ route('treatment.index') }}" class="text-xs font-semibold text-navy-700 hover:text-navy-900">View all treatment requests →</a>
                </div>
            </x-ui.card>
        @endif

        @if ($discrepancyList->isNotEmpty())
            <x-ui.card title="Open discrepancies" subtitle="Exceptions requiring resolution" padding="false">
                <div class="divide-y divide-slate-100">
                    @foreach ($discrepancyList as $discrepancy)
                        <a href="{{ route('discrepancies.show', $discrepancy) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-slate-50">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                                <x-icon name="alert" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-semibold text-slate-800">{{ $discrepancy->reference }}</p>
                                <p class="truncate text-[11px] text-slate-500">{{ $discrepancy->type->label() }} · {{ str($discrepancy->description)->limit(48) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="tabular text-xs font-semibold text-slate-900">{{ \App\Support\Money::format($discrepancy->amount) }}</p>
                                <x-ui.status-badge :status="$discrepancy->status" class="mt-0.5" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </x-ui.card>
        @endif
    </div>

    @if ($user->can('lodgements.create') && $lodgeableBatches > 0)
        <x-ui.alert variant="warning" title="Funds ready for bank lodgement">
            {{ \App\Support\Money::format($lodgeableBatches) }} across approved collection batches is not yet allocated to a bank deposit.
            <a href="{{ route('lodgements.create') }}" class="font-semibold underline decoration-amber-400 underline-offset-2">Create a lodgement</a>.
        </x-ui.alert>
    @endif

    <x-ui.card title="Recent payments" subtitle="Latest captured transactions in your scope" padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-2.5 text-left">Reference</th>
                        <th class="px-4 py-2.5 text-left">Payer</th>
                        <th class="px-4 py-2.5 text-left">Channel</th>
                        <th class="px-4 py-2.5 text-left">Branch</th>
                        <th class="px-4 py-2.5 text-right">Amount</th>
                        <th class="px-4 py-2.5 text-left">Status</th>
                        <th class="px-4 py-2.5 text-left">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($recentPayments as $payment)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <a href="{{ route('payments.show', $payment) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">{{ $payment->reference }}</a>
                            </td>
                            <td class="table-cell">{{ $payment->payer_name }}</td>
                            <td class="table-cell"><x-ui.channel-badge :channel="$payment->channel" /></td>
                            <td class="table-cell text-xs text-slate-500">{{ $payment->branch?->code }}</td>
                            <td class="table-cell text-right"><x-ui.money :amount="$payment->amount" :currency="$payment->currency" class="font-semibold" /></td>
                            <td class="table-cell"><x-ui.status-badge :status="$payment->status" /></td>
                            <td class="table-cell text-xs text-slate-500">{{ $payment->created_at?->format('H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"><x-ui.empty title="No payments recorded" message="Capture a payment to start the collection workflow." icon="banknotes" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-3">
            <a href="{{ route('payments.index') }}" class="text-xs font-semibold text-navy-700 hover:text-navy-900">View all payments →</a>
        </div>
    </x-ui.card>
</div>
