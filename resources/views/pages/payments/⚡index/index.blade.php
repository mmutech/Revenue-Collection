<div class="space-y-5">
    <x-ui.page-header title="Payments" subtitle="Every collection captured across AES, Direct Pay, cash, POS and cheque channels.">
        <x-slot:actions>
            @can('payments.create')
                <x-ui.button :href="route('payments.create')" icon="plus">Record payment</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card padding="false">
        <div class="border-b border-slate-100 p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                <x-ui.input wire:model.live.debounce.400ms="search" name="search" placeholder="Reference, payer, cheque…" icon="search" class="xl:col-span-2" />
                <x-ui.select wire:model.live="channel" name="channel" placeholder="All channels">
                    @foreach ($channels as $channelOption)
                        <option value="{{ $channelOption->id }}">{{ $channelOption->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select wire:model.live="status" name="status" placeholder="All statuses">
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                    @endforeach
                </x-ui.select>
                @if (count($branches) > 1)
                    <x-ui.select wire:model.live="branch" name="branch" placeholder="All branches">
                        @foreach ($branches as $branchOption)
                            <option value="{{ $branchOption->id }}">{{ $branchOption->code }} — {{ $branchOption->name }}</option>
                        @endforeach
                    </x-ui.select>
                @endif
                <div class="grid grid-cols-2 gap-2">
                    <x-ui.input wire:model.live="from" name="from" type="date" />
                    <x-ui.input wire:model.live="to" name="to" type="date" />
                </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <p class="text-[11px] text-slate-500">
                    Showing <span class="font-semibold text-slate-700">{{ $payments->count() }}</span> of
                    <span class="font-semibold text-slate-700">{{ $payments->total() }}</span> payments ·
                    filtered value <span class="tabular font-semibold text-slate-800">{{ \App\Support\Money::format($totals['amount']) }}</span>
                </p>
                <button type="button" wire:click="clearFilters" class="text-[11px] font-semibold text-slate-500 hover:text-navy-700">Reset filters</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3 text-left">Reference</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Payer</th>
                        <th class="px-4 py-3 text-left">Channel</th>
                        <th class="px-4 py-3 text-left">Branch</th>
                        <th class="px-4 py-3 text-left">Cashier</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($payments as $payment)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="table-cell">
                                <a href="{{ route('payments.show', $payment) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">{{ $payment->reference }}</a>
                                @if ($payment->receipt)
                                    <span class="ml-1.5 font-mono text-[10px] text-slate-400">{{ $payment->receipt->receipt_number }}</span>
                                @endif
                            </td>
                            <td class="table-cell text-xs">{{ $payment->payment_date?->format('j M Y') }}</td>
                            <td class="table-cell">
                                <p class="text-xs font-semibold text-slate-800">{{ $payment->payer_name }}</p>
                                @if ($payment->external_reference)
                                    <p class="font-mono text-[10px] text-slate-400">{{ $payment->external_reference }}</p>
                                @endif
                            </td>
                            <td class="table-cell"><x-ui.channel-badge :channel="$payment->channel" /></td>
                            <td class="table-cell text-xs text-slate-500">{{ $payment->branch?->code }}</td>
                            <td class="table-cell text-xs text-slate-600">{{ $payment->cashier?->name }}</td>
                            <td class="table-cell text-right">
                                <x-ui.money :amount="$payment->amount" :currency="$payment->currency" class="font-semibold text-slate-900" />
                            </td>
                            <td class="table-cell"><x-ui.status-badge :status="$payment->status" /></td>
                            <td class="table-cell text-right">
                                <a href="{{ route('payments.show', $payment) }}" class="text-slate-400 hover:text-navy-700" aria-label="View payment">
                                    <x-icon name="chevron-right" class="h-4 w-4" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <x-ui.empty title="No payments found" message="Adjust the filters or record a new payment." icon="banknotes">
                                    <x-slot:action>
                                        @can('payments.create')
                                            <x-ui.button :href="route('payments.create')" size="sm" icon="plus">Record payment</x-ui.button>
                                        @endcan
                                    </x-slot:action>
                                </x-ui.empty>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $payments->links() }}</div>
        @endif
    </x-ui.card>
</div>
