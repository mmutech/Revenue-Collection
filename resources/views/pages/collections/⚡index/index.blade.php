<div class="space-y-5">
    <x-ui.page-header title="Daily collection batches" subtitle="Cashier submissions grouped by business date and verified by clearing before bank lodgement.">
        <x-slot:actions>
            @can('batches.submit')
                <x-ui.button wire:click="openCreate" icon="plus">New daily batch</x-ui.button>
            @endcan
            @can('batches.verify')
                <x-ui.button :href="route('collections.queue')" variant="secondary" icon="scale">Clearing queue</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if ($showCreate)
        <x-ui.card title="Create daily batch" subtitle="All unbatched payments for the cashier and business date are captured automatically">
            <form wire:submit="create" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-ui.select wire:model="new_branch_id" name="new_branch_id" label="Branch" required>
                        @foreach ($branches as $branchOption)
                            <option value="{{ $branchOption->id }}">{{ $branchOption->code }} — {{ $branchOption->name }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.input wire:model="new_business_date" name="new_business_date" type="date" label="Business date" required :max="today()->toDateString()" />
                    <div class="flex items-end">
                        <x-ui.button type="submit" class="w-full" icon="check" wire:loading.attr="disabled">Create batch</x-ui.button>
                    </div>
                </div>
                <p class="text-[11px] text-slate-500">
                    A cashier can only have one batch per business date. Cheques must be cleared before they can be batched.
                </p>
            </form>
        </x-ui.card>
    @endif

    @if (session('status'))
        <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat label="Expected in view" :value="\App\Support\Money::compact($totals['expected'])" icon="clipboard" color="navy" />
        <x-ui.stat label="Not yet lodged" :value="\App\Support\Money::compact($totals['outstanding'])" icon="bank" color="amber" />
        <x-ui.stat label="Awaiting clearing" :value="$totals['pending']" icon="scale" color="rose" />
    </div>

    <x-ui.card padding="false">
        <div class="border-b border-slate-100 p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                <x-ui.input wire:model.live.debounce.400ms="search" name="search" placeholder="Search batch number…" icon="search" class="xl:col-span-2" />
                <x-ui.select wire:model.live="status" name="status" placeholder="All statuses">
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                    @endforeach
                </x-ui.select>
                @if (count($branches) > 1)
                    <x-ui.select wire:model.live="branch" name="branch" placeholder="All branches">
                        @foreach ($branches as $branchOption)
                            <option value="{{ $branchOption->id }}">{{ $branchOption->code }}</option>
                        @endforeach
                    </x-ui.select>
                @endif
                <div class="grid grid-cols-2 gap-2">
                    <x-ui.input wire:model.live="from" name="from" type="date" />
                    <x-ui.input wire:model.live="to" name="to" type="date" />
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between">
                <p class="text-[11px] text-slate-500">
                    {{ $batches->total() }} batches · <span class="tabular font-semibold text-slate-700">{{ \App\Support\Money::format($totals['expected']) }}</span> expected
                </p>
                <button type="button" wire:click="clearFilters" class="text-[11px] font-semibold text-slate-500 hover:text-navy-700">Reset filters</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3 text-left">Batch</th>
                        <th class="px-4 py-3 text-left">Business date</th>
                        <th class="px-4 py-3 text-left">Cashier</th>
                        <th class="px-4 py-3 text-left">Branch</th>
                        <th class="px-4 py-3 text-right">Items</th>
                        <th class="px-4 py-3 text-right">Expected</th>
                        <th class="px-4 py-3 text-right">Outstanding</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($batches as $batch)
                        @php($outstanding = max(0, (float) $batch->expected_total - (float) ($batch->allocated_sum ?? 0)))
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="table-cell">
                                <a href="{{ route('collections.show', $batch) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">{{ $batch->batch_number }}</a>
                                @if ((float) $batch->variance !== 0.0)
                                    <span class="ml-1.5 rounded bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-amber-700">Variance</span>
                                @endif
                            </td>
                            <td class="table-cell text-xs">{{ $batch->business_date?->format('j M Y') }}</td>
                            <td class="table-cell text-xs">
                                <span class="font-medium text-slate-800">{{ $batch->cashier?->name }}</span>
                                <span class="block text-[10px] text-slate-400">{{ $batch->submitted_at?->format('H:i') ? 'submitted '.$batch->submitted_at->format('H:i') : 'not submitted' }}</span>
                            </td>
                            <td class="table-cell text-xs text-slate-500">{{ $batch->branch?->code }}</td>
                            <td class="table-cell text-right text-xs">{{ $batch->item_count }}</td>
                            <td class="table-cell text-right"><x-ui.money :amount="$batch->expected_total" class="font-semibold text-slate-900" /></td>
                            <td class="table-cell text-right">
                                <span @class(['tabular font-semibold', 'text-amber-600' => $outstanding > 0, 'text-slate-400' => $outstanding <= 0])>
                                    {{ \App\Support\Money::format($outstanding) }}
                                </span>
                            </td>
                            <td class="table-cell"><x-ui.status-badge :status="$batch->status" /></td>
                            <td class="table-cell text-right">
                                <a href="{{ route('collections.show', $batch) }}" class="text-slate-400 hover:text-navy-700" aria-label="Open batch">
                                    <x-icon name="chevron-right" class="h-4 w-4" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9"><x-ui.empty title="No collection batches" message="Create a daily batch after capturing payments." icon="clipboard" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($batches->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $batches->links() }}</div>
        @endif
    </x-ui.card>
</div>
