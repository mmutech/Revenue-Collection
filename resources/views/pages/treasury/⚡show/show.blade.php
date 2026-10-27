@php($lodgement = $this->lodgement)
<div class="space-y-6">
    <x-ui.page-header title="Treasury review · {{ $lodgement->lodgement_number }}" subtitle="Verify the deposit against the bank statement and record the treasury decision." back="{{ route('treasury.index') }}">
        <x-slot:actions>
            <x-ui.button :href="route('lodgements.show', $lodgement)" variant="secondary" icon="document">Lodgement record</x-ui.button>
            <x-ui.status-badge :status="$lodgement->status" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($action_error)
        <x-ui.alert variant="error" title="Decision blocked">{{ $action_error }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat label="Deposit amount" :value="\App\Support\Money::compact($lodgement->amount)" icon="bank" color="navy" />
                <x-ui.stat label="Batches" :value="$lodgement->items->count()" icon="clipboard" color="blue" />
                <x-ui.stat label="Evidence" :value="$lodgement->documents->count()" icon="document" color="violet" />
                <x-ui.stat label="Variance" :value="\App\Support\Money::compact($variance)" :hint="abs($variance) < 0.01 ? 'Matches approved amount' : 'Requires explanation'" icon="scale" :color="abs($variance) < 0.01 ? 'mint' : 'rose'" />
            </div>

            <x-ui.card title="Deposit record">
                <dl class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-slate-500">Bank</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->bank?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Deposit slip</dt>
                        <dd class="mt-0.5 font-mono font-medium text-slate-800">{{ $lodgement->deposit_slip_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Deposit date</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->deposit_date?->format('j M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Branch</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->branch?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Deposited by</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->deposited_by_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Submitted by</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->submittedBy?->name ?? $lodgement->creator?->name }}</dd>
                    </div>
                </dl>

                @if ($lodgement->notes)
                    <p class="mt-4 rounded-lg bg-slate-50 px-3.5 py-3 text-xs text-slate-600">{{ $lodgement->notes }}</p>
                @endif
            </x-ui.card>

            <x-ui.card title="Allocations" padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3 text-left">Batch</th>
                                <th class="px-4 py-3 text-left">Cashier</th>
                                <th class="px-4 py-3 text-left">Business date</th>
                                <th class="px-4 py-3 text-right">Allocated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($lodgement->items as $item)
                                <tr>
                                    <td class="table-cell">
                                        <a href="{{ route('collections.show', $item->batch) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">{{ $item->batch?->batch_number }}</a>
                                    </td>
                                    <td class="table-cell text-xs">{{ $item->batch?->cashier?->name }}</td>
                                    <td class="table-cell text-xs text-slate-500">{{ $item->batch?->business_date?->format('j M Y') }}</td>
                                    <td class="table-cell text-right"><x-ui.money :amount="$item->amount" class="font-semibold" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <x-ui.card title="Supporting evidence" padding="false">
                @if ($lodgement->documents->isEmpty())
                    <x-ui.empty title="No evidence" message="No deposit slip was attached to this lodgement." icon="document" />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($lodgement->documents as $document)
                            <div class="flex items-center gap-3 px-5 py-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                    <x-icon name="document" class="h-4 w-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-slate-800">{{ $document->original_name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ number_format(((int) $document->size) / 1024, 1) }} KB</p>
                                </div>
                                <x-ui.button :href="route('documents.lodgement', $document)" size="sm" variant="secondary" icon="download">Download</x-ui.button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            @if ($lodgement->treasuryVerifications->isNotEmpty())
                <x-ui.card title="Decision history" padding="false">
                    <ol class="divide-y divide-slate-100">
                        @foreach ($lodgement->treasuryVerifications as $verification)
                            <li class="px-5 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-semibold text-slate-700">
                                        {{ \App\Enums\TreasuryDecision::tryFrom($verification->decision)?->label() ?? $verification->decision }}
                                    </p>
                                    <span class="text-[11px] text-slate-400">{{ $verification->verified_at?->format('j M Y H:i') }}</span>
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500">
                                    {{ $verification->officer?->name }} · approved {{ \App\Support\Money::format($verification->approved_amount) }} · deposited {{ \App\Support\Money::format($verification->deposited_amount) }} · variance {{ \App\Support\Money::format($verification->variance) }}
                                </p>
                                @if ($verification->notes)
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $verification->notes }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </x-ui.card>
            @endif
        </div>

        <div class="space-y-6">
            @if ($canDecide)
                <x-ui.card title="Record treasury decision" subtitle="Confirmation recognises the funds as banked">
                    <form wire:submit="decide" class="space-y-3">
                        <x-ui.select wire:model.live="decision" name="decision" label="Decision" required>
                            <option value="confirmed">Confirmed — funds received</option>
                            <option value="returned">Returned — correct and resubmit</option>
                            <option value="rejected">Rejected — not valid</option>
                            <option value="escalated">Escalated — investigate</option>
                        </x-ui.select>

                        <x-ui.input wire:model.live.debounce.300ms="deposited_amount" name="deposited_amount" type="number" step="0.01" min="0" label="Amount credited by bank" required icon="banknotes" />

                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Approved amount</span>
                                <span class="tabular font-semibold text-slate-800">{{ \App\Support\Money::format($lodgement->amount) }}</span>
                            </div>
                            <div class="mt-1 flex items-center justify-between">
                                <span class="text-slate-500">Variance</span>
                                <span @class(['tabular font-semibold', 'text-emerald-600' => abs($variance) < 0.01, 'text-rose-600' => abs($variance) >= 0.01])>
                                    {{ \App\Support\Money::format($variance) }}
                                </span>
                            </div>
                        </div>

                        @if ($decision === 'confirmed')
                            <x-ui.input wire:model="bank_confirmation_reference" name="bank_confirmation_reference" label="Bank confirmation reference" required icon="document" help="Reference from the bank statement or credit advice." />
                        @endif

                        <x-ui.textarea wire:model="notes" name="notes" rows="3" label="Notes" :help="$decision === 'confirmed' ? 'Required when the credited amount differs from the approved amount.' : 'Required for returned, rejected or escalated decisions.'" />

                        <x-ui.button type="submit" class="w-full" icon="shield" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="decide">Record decision</span>
                            <span wire:loading wire:target="decide">Recording…</span>
                        </x-ui.button>
                    </form>
                </x-ui.card>
            @elseif ($isOwnSubmission)
                <x-ui.alert variant="warning" title="Segregation of duties">
                    You created or submitted this lodgement. A different treasury officer must confirm it.
                </x-ui.alert>
            @elseif ($lodgement->status === LodgementStatus::UnderTreasuryReview)
                <x-ui.alert variant="info" title="No confirmation permission">
                    You can review this deposit but cannot record a treasury decision.
                </x-ui.alert>
            @else
                <x-ui.card title="Decision recorded">
                    <div class="flex items-center gap-2">
                        <x-ui.status-badge :status="$lodgement->status" />
                    </div>
                    @if ($lodgement->treasury_verified_at)
                        <p class="mt-3 text-xs text-slate-500">
                            Confirmed by {{ $lodgement->treasuryVerifiedBy?->name }} on {{ $lodgement->treasury_verified_at->format('j M Y H:i') }}.
                        </p>
                    @endif
                    @if ($lodgement->bank_confirmation_reference)
                        <p class="mt-1 font-mono text-[11px] text-slate-500">Bank reference: {{ $lodgement->bank_confirmation_reference }}</p>
                    @endif
                </x-ui.card>
            @endif

            <x-ui.alert variant="info" title="Verification standard">
                Confirm only when the credit appears on the bank statement and the amount, date and reference reconcile to this lodgement.
            </x-ui.alert>
        </div>
    </div>
</div>
