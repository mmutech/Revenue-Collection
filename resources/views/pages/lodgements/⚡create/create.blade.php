<div class="space-y-5">
    <x-ui.page-header title="New bank lodgement" subtitle="Allocate approved collection batches to a physical bank deposit." back="{{ route('lodgements.index') }}" />

    @if ($form_error)
        <x-ui.alert variant="error" title="Lodgement could not be created">{{ $form_error }}</x-ui.alert>
    @endif

    <form wire:submit="save" class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Deposit details" subtitle="Bank, slip and deposit information">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.select wire:model.live="branch_id" name="branch_id" label="Branch" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->code }} — {{ $branch->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select wire:model.live="bank_id" name="bank_id" label="Bank" placeholder="Select bank" required>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select wire:model="bank_branch_id" name="bank_branch_id" label="Bank branch" placeholder="Optional">
                        @foreach ($this->bankBranches as $bankBranch)
                            <option value="{{ $bankBranch->id }}">{{ $bankBranch->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.input wire:model="deposit_slip_number" name="deposit_slip_number" label="Deposit slip number" required placeholder="e.g. SLIP-482910" />
                    <x-ui.input wire:model="deposit_date" name="deposit_date" type="date" label="Deposit date" required :max="today()->toDateString()" />
                    <x-ui.input wire:model="deposited_by_name" name="deposited_by_name" label="Deposited by" required />
                </div>

                <x-ui.textarea wire:model="notes" name="notes" rows="2" label="Notes" class="mt-4" />
            </x-ui.card>

            <x-ui.card title="Batch allocations" subtitle="Only approved batches with an outstanding balance are shown" padding="false">
                @if ($batches->isEmpty())
                    <x-ui.empty title="No batches available" message="Approve collection batches for lodgement first." icon="clipboard" />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-5 py-3 text-left">Batch</th>
                                    <th class="px-4 py-3 text-left">Cashier</th>
                                    <th class="px-4 py-3 text-left">Date</th>
                                    <th class="px-4 py-3 text-right">Expected</th>
                                    <th class="px-4 py-3 text-right">Available</th>
                                    <th class="px-4 py-3 text-right">Allocate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach ($batches as $batch)
                                    <tr>
                                        <td class="table-cell">
                                            <a href="{{ route('collections.show', $batch) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">{{ $batch->batch_number }}</a>
                                            <x-ui.status-badge :status="$batch->status" class="ml-2" />
                                        </td>
                                        <td class="table-cell text-xs">{{ $batch->cashier?->name }}</td>
                                        <td class="table-cell text-xs text-slate-500">{{ $batch->business_date?->format('j M Y') }}</td>
                                        <td class="table-cell text-right"><x-ui.money :amount="$batch->expected_total" /></td>
                                        <td class="table-cell text-right text-xs font-semibold text-amber-600">{{ \App\Support\Money::format($batch->available_amount) }}</td>
                                        <td class="table-cell text-right">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="{{ $batch->available_amount }}"
                                                wire:model.live.debounce.300ms="allocations.{{ $batch->id }}"
                                                class="input-base w-32 text-right"
                                            >
                                            @error('allocations.'.$batch->id)
                                                <p class="mt-1 text-[10px] font-medium text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @error('allocations')
                    <p class="border-t border-rose-100 bg-rose-50 px-5 py-2 text-[11px] font-medium text-rose-700">{{ $message }}</p>
                @enderror
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Lodgement summary">
                <dl class="space-y-3 text-xs">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Batches selected</dt>
                        <dd class="font-semibold text-slate-800">{{ collect($allocations)->filter(fn ($amount) => (float) $amount > 0)->count() }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Bank</dt>
                        <dd class="font-semibold text-slate-800">{{ $banks->firstWhere('id', (int) $bank_id)?->short_name ?? 'Not selected' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-t border-slate-100 pt-3">
                        <dt class="text-slate-500">Total deposit</dt>
                        <dd class="tabular text-base font-semibold text-slate-950">{{ \App\Support\Money::format($selectedTotal) }}</dd>
                    </div>
                </dl>

                <div class="mt-5 space-y-2 border-t border-slate-100 pt-4">
                    <x-ui.button type="submit" class="w-full" icon="check" wire:loading.attr="disabled" :disabled="$batches->isEmpty()">
                        Create draft lodgement
                    </x-ui.button>
                    <x-ui.button :href="route('lodgements.index')" variant="secondary" class="w-full">Cancel</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.alert variant="info" title="Evidence required">
                A draft lodgement does not move funds. You must attach the stamped deposit slip before submitting it to treasury.
            </x-ui.alert>
        </div>
    </form>
</div>
