<div class="space-y-5">
    <x-ui.page-header title="Bank lodgements" subtitle="Allocate approved collection batches to bank deposits and track treasury confirmation.">
        <x-slot:actions>
            @can('lodgements.create')
                <x-ui.button :href="route('lodgements.create')" icon="plus">New lodgement</x-ui.button>
            @endcan
            @can('treasury.view')
                <x-ui.button :href="route('treasury.index')" variant="secondary" icon="shield">Treasury workspace</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat label="Value in view" :value="\App\Support\Money::compact($totals['amount'])" icon="bank" color="navy" />
        <x-ui.stat label="Awaiting treasury" :value="$totals['awaiting']" icon="shield" color="purple" />
        <x-ui.stat label="Records" :value="$lodgements->total()" icon="document" color="blue" />
    </div>

    <x-ui.card padding="false">
        <div class="border-b border-slate-100 p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <x-ui.input wire:model.live.debounce.400ms="search" name="search" placeholder="Lodgement, slip or bank reference…" icon="search" class="md:col-span-2" />
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
            </div>
            <div class="mt-3 flex items-center justify-between">
                <p class="text-[11px] text-slate-500">{{ $lodgements->total() }} lodgements</p>
                <button type="button" wire:click="clearFilters" class="text-[11px] font-semibold text-slate-500 hover:text-navy-700">Reset filters</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3 text-left">Lodgement</th>
                        <th class="px-4 py-3 text-left">Deposit date</th>
                        <th class="px-4 py-3 text-left">Bank</th>
                        <th class="px-4 py-3 text-left">Branch</th>
                        <th class="px-4 py-3 text-right">Batches</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($lodgements as $lodgement)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="table-cell">
                                <a href="{{ route('lodgements.show', $lodgement) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">{{ $lodgement->lodgement_number }}</a>
                                <span class="block font-mono text-[10px] text-slate-400">slip {{ $lodgement->deposit_slip_number }}</span>
                            </td>
                            <td class="table-cell text-xs">{{ $lodgement->deposit_date?->format('j M Y') }}</td>
                            <td class="table-cell text-xs">
                                {{ $lodgement->bank?->short_name ?? $lodgement->bank?->name }}
                                @if ($lodgement->bankBranch)
                                    <span class="block text-[10px] text-slate-400">{{ $lodgement->bankBranch->name }}</span>
                                @endif
                            </td>
                            <td class="table-cell text-xs text-slate-500">{{ $lodgement->branch?->code }}</td>
                            <td class="table-cell text-right text-xs">{{ $lodgement->items_count }}</td>
                            <td class="table-cell text-right"><x-ui.money :amount="$lodgement->amount" class="font-semibold text-slate-900" /></td>
                            <td class="table-cell"><x-ui.status-badge :status="$lodgement->status" /></td>
                            <td class="table-cell text-right">
                                <a href="{{ route('lodgements.show', $lodgement) }}" class="text-slate-400 hover:text-navy-700" aria-label="Open lodgement">
                                    <x-icon name="chevron-right" class="h-4 w-4" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"><x-ui.empty title="No lodgements" message="Create a lodgement once collection batches are approved for banking." icon="bank" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($lodgements->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $lodgements->links() }}</div>
        @endif
    </x-ui.card>
</div>
