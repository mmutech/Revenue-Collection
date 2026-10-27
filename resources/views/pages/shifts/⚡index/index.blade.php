<div class="space-y-6">
    <x-ui.page-header title="Cashier shifts" subtitle="Open a cash drawer, record cash collections and reconcile the count at close.">
        <x-slot:actions>
            <x-ui.button :href="route('payments.create')" variant="secondary" icon="plus">Record payment</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            @if ($openShift)
                <div class="card-surface overflow-hidden">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 bg-mint-50/40 px-5 py-5">
                        <div class="flex items-start gap-3">
                            <span class="relative mt-1 flex h-3 w-3">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-mint-500 opacity-60"></span>
                                <span class="relative inline-flex h-3 w-3 rounded-full bg-mint-600"></span>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Shift open</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $openShift->branch?->name }} · {{ $openShift->collectionPoint?->name ?? 'No collection point' }}
                                </p>
                                <p class="text-[11px] text-slate-400">Opened {{ $openShift->opened_at?->format('j M Y H:i') }}</p>
                            </div>
                        </div>
                        <x-ui.status-badge :status="$openShift->status" />
                    </div>

                    <dl class="grid grid-cols-2 divide-x divide-slate-100 sm:grid-cols-4">
                        <div class="px-4 py-4">
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Opening float</dt>
                            <dd class="tabular mt-1 text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($openShift->opening_float) }}</dd>
                        </div>
                        <div class="px-4 py-4">
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Cash collected</dt>
                            <dd class="tabular mt-1 text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($shiftSummary['collections']) }}</dd>
                        </div>
                        <div class="px-4 py-4">
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Expected in drawer</dt>
                            <dd class="tabular mt-1 text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($shiftSummary['expected']) }}</dd>
                        </div>
                        <div class="px-4 py-4">
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Business date</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $openShift->business_date?->format('j M Y') }}</dd>
                        </div>
                    </dl>

                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-3">
                        <p class="text-[11px] text-slate-500">Close the shift only after all cash payments for the day have been captured.</p>
                        <x-ui.button wire:click="startClose({{ $openShift->id }})" size="sm" variant="secondary" icon="lock">Close shift</x-ui.button>
                    </div>
                </div>

                @if ($closingId === $openShift->id)
                    <x-ui.card title="Close shift" subtitle="Count the physical drawer and record the total">
                        <form wire:submit="close" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <x-ui.input wire:model="counted_cash" name="counted_cash" type="number" step="0.01" min="0" label="Counted cash" required icon="banknotes" />
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Expected</p>
                                    <p class="tabular mt-0.5 text-sm font-semibold text-slate-800">{{ \App\Support\Money::format($shiftSummary['expected']) }}</p>
                                </div>
                            </div>
                            <x-ui.textarea wire:model="close_notes" name="close_notes" rows="2" label="Notes" help="Explain any difference between the expected and counted cash." />
                            <div class="flex justify-end gap-2">
                                <x-ui.button type="button" variant="ghost" wire:click="cancelClose">Cancel</x-ui.button>
                                <x-ui.button type="submit" icon="check" wire:loading.attr="disabled">Close and reconcile</x-ui.button>
                            </div>
                        </form>
                    </x-ui.card>
                @endif
            @else
                <x-ui.alert variant="info" title="No open shift">
                    Open a shift before recording cash payments. Non-cash channels can be captured at any time.
                </x-ui.alert>
            @endif

            <x-ui.card title="Shift history" subtitle="Recent cashier sessions in your scope" padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3 text-left">Business date</th>
                                <th class="px-4 py-3 text-left">Cashier</th>
                                <th class="px-4 py-3 text-left">Point</th>
                                <th class="px-4 py-3 text-right">Float</th>
                                <th class="px-4 py-3 text-right">Expected</th>
                                <th class="px-4 py-3 text-right">Counted</th>
                                <th class="px-4 py-3 text-right">Variance</th>
                                <th class="px-4 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($shifts as $shiftRow)
                                <tr class="transition hover:bg-slate-50/60">
                                    <td class="table-cell text-xs">{{ $shiftRow->business_date?->format('j M Y') }}</td>
                                    <td class="table-cell text-xs">
                                        <span class="font-medium text-slate-800">{{ $shiftRow->user?->name }}</span>
                                        <span class="block text-[10px] text-slate-400">{{ $shiftRow->branch?->code }}</span>
                                    </td>
                                    <td class="table-cell text-xs text-slate-500">{{ $shiftRow->collectionPoint?->name ?? '—' }}</td>
                                    <td class="table-cell text-right"><x-ui.money :amount="$shiftRow->opening_float" /></td>
                                    <td class="table-cell text-right"><x-ui.money :amount="$shiftRow->expected_cash ?? 0" /></td>
                                    <td class="table-cell text-right"><x-ui.money :amount="$shiftRow->counted_cash ?? 0" /></td>
                                    <td class="table-cell text-right">
                                        @if ($shiftRow->variance !== null)
                                            <span @class(['tabular font-semibold', 'text-rose-600' => (float) $shiftRow->variance < -0.001, 'text-emerald-600' => abs((float) $shiftRow->variance) < 0.001, 'text-amber-600' => (float) $shiftRow->variance > 0.001])>
                                                {{ \App\Support\Money::format($shiftRow->variance) }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="table-cell"><x-ui.status-badge :status="$shiftRow->status" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8"><x-ui.empty title="No shifts yet" message="Open your first shift to start collecting cash." icon="clock" /></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($shifts->hasPages())
                    <div class="border-t border-slate-100 px-5 py-3">{{ $shifts->links() }}</div>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            @if (! $openShift)
                <x-ui.card title="Open a shift" subtitle="A shift covers one business date and cash drawer">
                    <form wire:submit="open" class="space-y-4">
                        <x-ui.select wire:model.live="branch_id" name="branch_id" label="Branch" required>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->code }} — {{ $branch->name }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select wire:model="collection_point_id" name="collection_point_id" label="Collection point" placeholder="Use my default point">
                            @foreach ($points as $point)
                                <option value="{{ $point->id }}">{{ $point->name }} ({{ $point->branch?->code }})</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.input wire:model="opening_float" name="opening_float" type="number" step="0.01" min="0" label="Opening float" required icon="banknotes" />

                        <x-ui.textarea wire:model="notes" name="notes" rows="2" label="Notes" />

                        <x-ui.button type="submit" class="w-full" icon="lock" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="open">Open shift</span>
                            <span wire:loading wire:target="open">Opening…</span>
                        </x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            <x-ui.card title="Shift controls">
                <ul class="space-y-3 text-xs text-slate-600">
                    <li class="flex gap-2.5">
                        <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-mint-600" />
                        One open shift per cashier at any time, enforced in the database and service layer.
                    </li>
                    <li class="flex gap-2.5">
                        <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-mint-600" />
                        Cash payments require an open shift; non-cash channels are not restricted.
                    </li>
                    <li class="flex gap-2.5">
                        <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-mint-600" />
                        Any count variance automatically raises a discrepancy assigned to the cashier.
                    </li>
                </ul>
            </x-ui.card>
        </div>
    </div>
</div>
