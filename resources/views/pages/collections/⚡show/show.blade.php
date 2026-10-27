@php
    $batch = $this->batch;
    $variance = (float) $batch->variance;
@endphp

<div class="space-y-6">
    <x-ui.page-header title="Batch {{ $batch->batch_number }}" subtitle="Cashier collection submission, clearing verification and lodgement control." back="{{ route('collections.index') }}">
        <x-slot:actions>
            @if ($batch->status === \App\Enums\BatchStatus::ApprovedForLodgement || $batch->status === \App\Enums\BatchStatus::PartiallyLodged)
                @can('lodgements.create')
                    <x-ui.button :href="route('lodgements.create')" variant="secondary" icon="bank">Lodge funds</x-ui.button>
                @endcan
            @endif
            <x-ui.status-badge :status="$batch->status" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($action_error)
        <x-ui.alert variant="error" title="Action blocked">{{ $action_error }}</x-ui.alert>
    @endif

    @if (session('status'))
        <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($batch->status === \App\Enums\BatchStatus::ReturnedForCorrection && $batch->return_reason)
        <x-ui.alert variant="warning" title="Returned for correction">
            {{ $batch->return_reason }}
            <span class="block text-[11px] opacity-80">{{ $batch->returned_at?->format('j M Y H:i') }}</span>
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Expected total" :value="\App\Support\Money::compact($batch->expected_total)" :hint="$batch->item_count.' payments captured'" icon="clipboard" color="navy" />
        <x-ui.stat label="Declared by cashier" :value="\App\Support\Money::compact($batch->declared_total ?? 0)" icon="banknotes" color="blue" />
        <x-ui.stat label="Counted by clearing" :value="\App\Support\Money::compact($batch->counted_total ?? 0)" :hint="$batch->verified_at ? 'Verified '.$batch->verified_at->format('j M H:i') : 'Awaiting verification'" icon="scale" color="amber" />
        <x-ui.stat
            label="Variance"
            :value="\App\Support\Money::compact($variance)"
            :hint="abs($variance) < 0.01 ? 'Balanced' : 'Difference raised as discrepancy'"
            icon="alert"
            :color="abs($variance) < 0.01 ? 'mint' : 'rose'"
        />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Batch details">
                <dl class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-slate-500">Cashier</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $batch->cashier?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Branch</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $batch->branch?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Collection point</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $batch->collectionPoint?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Business date</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $batch->business_date?->format('j M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Shift</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">
                            {{ $batch->shift?->business_date?->format('j M Y') ?? 'No shift linked' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Submitted</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">
                            {{ $batch->submitted_at?->format('j M Y H:i') ?? 'Not submitted' }}
                            @if ($batch->submittedBy)
                                <span class="block text-[10px] text-slate-400">by {{ $batch->submittedBy->name }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Verified by</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $batch->verifiedBy?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Approved for lodgement</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">
                            {{ $batch->approved_at?->format('j M Y H:i') ?? '—' }}
                            @if ($batch->approvedBy)
                                <span class="block text-[10px] text-slate-400">by {{ $batch->approvedBy->name }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Not yet lodged</dt>
                        <dd class="mt-0.5 font-semibold text-amber-600">{{ \App\Support\Money::format($batch->outstandingAmount()) }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Payments in this batch" :subtitle="$batch->item_count.' transactions'" padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3 text-left">Reference</th>
                                <th class="px-4 py-3 text-left">Payer</th>
                                <th class="px-4 py-3 text-left">Channel</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                @if ($canSubmit)
                                    <th class="px-4 py-3"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($batch->items as $item)
                                <tr class="transition hover:bg-slate-50/60">
                                    <td class="table-cell">
                                        <a href="{{ route('payments.show', $item->payment) }}" class="font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">{{ $item->payment?->reference }}</a>
                                    </td>
                                    <td class="table-cell text-xs">{{ $item->payment?->payer_name }}</td>
                                    <td class="table-cell"><x-ui.channel-badge :channel="$item->payment?->channel" /></td>
                                    <td class="table-cell text-right"><x-ui.money :amount="$item->amount" class="font-semibold text-slate-900" /></td>
                                    <td class="table-cell"><x-ui.status-badge :status="$item->payment?->status" /></td>
                                    @if ($canSubmit)
                                        <td class="table-cell text-right">
                                            <button type="button" wire:click="removePayment({{ $item->payment_id }})" class="text-rose-500 hover:text-rose-700" aria-label="Remove payment">
                                                <x-icon name="x-circle" class="h-4 w-4" />
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"><x-ui.empty title="No payments in this batch" message="Add eligible payments before submitting." icon="banknotes" /></td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t border-slate-200 bg-slate-50/60">
                            <tr>
                                <td colspan="3" class="px-5 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total</td>
                                <td class="px-4 py-2.5 text-right"><x-ui.money :amount="$batch->expected_total" class="text-sm font-semibold text-slate-950" /></td>
                                <td colspan="{{ $canSubmit ? 2 : 1 }}"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-ui.card>

            @if ($availablePayments->isNotEmpty())
                <x-ui.card title="Add eligible payments" subtitle="Unbatched, cleared funds for this cashier and business date" padding="false">
                    <div class="divide-y divide-slate-100">
                        @foreach ($availablePayments as $payment)
                            <div class="flex items-center gap-3 px-5 py-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                    <x-icon name="banknotes" class="h-4 w-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-mono text-xs font-semibold text-slate-800">{{ $payment->reference }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $payment->payer_name }} · {{ $payment->channel?->name }}</p>
                                </div>
                                <x-ui.money :amount="$payment->amount" class="text-xs font-semibold" />
                                <x-ui.button wire:click="addPayment({{ $payment->id }})" size="sm" variant="secondary" icon="plus">Add</x-ui.button>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif

            <x-ui.card title="Lodgement allocations" subtitle="Bank deposits that include this batch" padding="false">
                @if ($lodgementItems->isEmpty())
                    <x-ui.empty title="Not yet allocated" message="Approved batches appear in the lodgement workspace once released for banking." icon="bank" />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($lodgementItems as $item)
                            <a href="{{ route('lodgements.show', $item->lodgement) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-slate-50">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                                    <x-icon name="bank" class="h-4 w-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-mono text-xs font-semibold text-slate-800">{{ $item->lodgement?->lodgement_number }}</p>
                                    <p class="text-[11px] text-slate-500">
                                        {{ $item->lodgement?->bank?->short_name ?? $item->lodgement?->bank?->name }} · slip {{ $item->lodgement?->deposit_slip_number }}
                                    </p>
                                </div>
                                <x-ui.money :amount="$item->amount" class="text-xs font-semibold" />
                                <x-ui.status-badge :status="$item->lodgement?->status" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Workflow history" padding="false">
                @if ($transitions->isEmpty())
                    <x-ui.empty title="No transitions" message="Status changes will be recorded here." icon="clock" />
                @else
                    <ol class="divide-y divide-slate-100">
                        @foreach ($transitions as $transition)
                            <li class="flex items-start gap-3 px-5 py-3">
                                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                    <x-icon name="refresh" class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-700">
                                        {{ str($transition->from_status ?? 'created')->replace('_', ' ')->headline() }}
                                        <span class="text-slate-400">→</span>
                                        {{ str($transition->to_status)->replace('_', ' ')->headline() }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">{{ $transition->actor?->name ?? 'System' }}@if ($transition->reason) · {{ $transition->reason }} @endif</p>
                                </div>
                                <time class="text-[11px] text-slate-400">{{ $transition->created_at?->format('j M H:i') }}</time>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            @if ($batch->latestVerification)
                <x-ui.card title="Clearing verification">
                    <dl class="space-y-2.5 text-xs">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Decision</dt>
                            <dd><x-ui.status-badge :status="$batch->latestVerification->decision" /></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Counted total</dt>
                            <dd class="font-semibold text-slate-800">{{ \App\Support\Money::format($batch->latestVerification->counted_total ?? 0) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Variance</dt>
                            <dd class="font-semibold text-slate-800">{{ \App\Support\Money::format($batch->latestVerification->variance ?? 0) }}</dd>
                        </div>
                    </dl>
                    @if ($batch->latestVerification->notes)
                        <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-[11px] text-slate-600">{{ $batch->latestVerification->notes }}</p>
                    @endif
                </x-ui.card>
            @endif

            @if ($canSubmit)
                <x-ui.card title="Submit for clearing" subtitle="Confirm the declared total">
                    <form wire:submit="submit" class="space-y-3">
                        <x-ui.input wire:model="declared_total" name="declared_total" type="number" step="0.01" min="0" label="Declared total" required icon="banknotes" help="The amount counted by the cashier." />
                        <x-ui.button type="submit" class="w-full" icon="paper-airplane" wire:loading.attr="disabled">Submit batch</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            @if ($canVerify)
                <x-ui.card title="Record verification" subtitle="Counted funds must agree before lodgement">
                    <form wire:submit="verify" class="space-y-3">
                        <x-ui.select wire:model.live="verification_decision" name="verification_decision" label="Decision" required>
                            <option value="verified">Verified — count agrees</option>
                            <option value="returned">Return for correction</option>
                        </x-ui.select>
                        @if ($verification_decision === 'verified')
                            <x-ui.input wire:model="counted_total" name="counted_total" type="number" step="0.01" min="0" label="Counted total" required icon="scale" />
                        @endif
                        <x-ui.textarea wire:model="verification_notes" name="verification_notes" rows="3" label="Notes" help="Required when the count differs from the expected total." />
                        <x-ui.button type="submit" class="w-full" icon="check" wire:loading.attr="disabled">
                            Record decision
                        </x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            @if ($canApprove)
                <x-ui.card title="Approve for lodgement" subtitle="A second officer releases the funds to banking">
                    <p class="text-xs leading-relaxed text-slate-600">
                        The verifier and the cashier cannot approve this batch. Approval makes the funds available in the bank lodgement workspace.
                    </p>
                    <x-ui.button wire:click="approve" class="mt-4 w-full" icon="shield" wire:loading.attr="disabled">Approve batch</x-ui.button>
                </x-ui.card>
            @endif

            @if ($canClose)
                <x-ui.card title="Close batch" subtitle="All funds have been confirmed by treasury">
                    <p class="text-xs leading-relaxed text-slate-600">
                        Closing completes the operational record. It cannot be reversed.
                    </p>
                    <x-ui.button wire:click="close" class="mt-4 w-full" variant="secondary" icon="lock" wire:loading.attr="disabled">Close batch</x-ui.button>
                </x-ui.card>
            @endif

            <x-ui.alert variant="info" title="Control summary">
                Cashier → clearing verification → supervisor approval → bank lodgement → treasury confirmation. Each step is independently attributed and audited.
            </x-ui.alert>
        </div>
    </div>
</div>
