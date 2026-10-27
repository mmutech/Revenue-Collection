@php
    $payment = $this->payment;
    $isCheque = $payment->isCheque();
@endphp

<div class="space-y-6">
    <x-ui.page-header title="Payment {{ $payment->reference }}" subtitle="Full transaction record, clearance status and audit history." back="{{ route('payments.index') }}">
        <x-slot:actions>
            @if ($payment->receipt && (auth()->user()->can('receipts.issue') || auth()->user()->can('receipts.reprint')))
                <x-ui.button :href="route('payments.receipt', $payment)" variant="secondary" icon="print">Print receipt</x-ui.button>
            @endif
            @if ($payment->currentBatch())
                <x-ui.button :href="route('collections.show', $payment->currentBatch())" variant="secondary" icon="clipboard">View batch</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('status'))
        <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card-surface overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-5 py-5">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Amount collected</p>
                        <p class="tabular mt-1 text-3xl font-semibold tracking-tight text-slate-950">
                            {{ \App\Support\Money::format($payment->amount, $payment->currency) }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">{{ $payment->payer_name }} · {{ $payment->payment_date?->format('j F Y') }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <x-ui.status-badge :status="$payment->status" />
                        @if ($isCheque)
                            <x-ui.status-badge :status="$payment->cheque_status" />
                        @endif
                        <x-ui.channel-badge :channel="$payment->channel" />
                    </div>
                </div>

                <dl class="grid grid-cols-1 divide-y divide-slate-100 sm:grid-cols-2 sm:divide-y-0">
                    <div class="space-y-3 px-5 py-4">
                        <div class="flex justify-between gap-3 text-xs">
                            <dt class="text-slate-500">Reference</dt>
                            <dd class="font-mono font-semibold text-slate-800">{{ $payment->reference }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 text-xs">
                            <dt class="text-slate-500">Branch</dt>
                            <dd class="font-medium text-slate-800">{{ $payment->branch?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 text-xs">
                            <dt class="text-slate-500">Collection point</dt>
                            <dd class="font-medium text-slate-800">{{ $payment->collectionPoint?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 text-xs">
                            <dt class="text-slate-500">Cashier</dt>
                            <dd class="font-medium text-slate-800">{{ $payment->cashier?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 text-xs">
                            <dt class="text-slate-500">Recorded by</dt>
                            <dd class="font-medium text-slate-800">{{ $payment->creator?->name ?? '—' }}</dd>
                        </div>
                    </div>
                    <div class="space-y-3 px-5 py-4">
                        <div class="flex justify-between gap-3 text-xs">
                            <dt class="text-slate-500">Channel</dt>
                            <dd class="font-medium text-slate-800">{{ $payment->channel?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 text-xs">
                            <dt class="text-slate-500">Payment date</dt>
                            <dd class="font-medium text-slate-800">{{ $payment->payment_date?->format('j M Y') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 text-xs">
                            <dt class="text-slate-500">Recorded at</dt>
                            <dd class="font-medium text-slate-800">{{ $payment->created_at?->format('j M Y H:i') }}</dd>
                        </div>
                        @if ($payment->external_reference)
                            <div class="flex justify-between gap-3 text-xs">
                                <dt class="text-slate-500">External reference</dt>
                                <dd class="font-mono font-medium text-slate-800">{{ $payment->external_reference }}</dd>
                            </div>
                        @endif
                        @if ($payment->provider)
                            <div class="flex justify-between gap-3 text-xs">
                                <dt class="text-slate-500">Provider</dt>
                                <dd class="font-medium text-slate-800">{{ $payment->provider }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-3 text-xs">
                            <dt class="text-slate-500">Currency</dt>
                            <dd class="font-medium text-slate-800">{{ $payment->currency }}</dd>
                        </div>
                    </div>
                </dl>
            </div>

            @if ($isCheque)
                <x-ui.card title="Cheque details" subtitle="Negotiable instrument accepted at the cash desk">
                    <dl class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-2">
                        <div>
                            <dt class="text-slate-500">Cheque number</dt>
                            <dd class="mt-0.5 font-mono font-semibold text-slate-800">{{ $payment->cheque_number ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Cheque date</dt>
                            <dd class="mt-0.5 font-medium text-slate-800">{{ $payment->cheque_date?->format('j M Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Drawer</dt>
                            <dd class="mt-0.5 font-medium text-slate-800">{{ $payment->cheque_drawer ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Bank</dt>
                            <dd class="mt-0.5 font-medium text-slate-800">
                                {{ $payment->bank?->name ?? '—' }}
                                @if ($payment->bankBranch)
                                    <span class="text-slate-400"> · {{ $payment->bankBranch->name }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if ($payment->cheque_status === \App\Enums\ChequeStatus::Dishonoured)
                        <x-ui.alert variant="error" class="mt-4" title="Cheque dishonoured">
                            This cheque was not honoured by the drawee bank. The payment cannot be included in a collection batch.
                        </x-ui.alert>
                    @elseif ($payment->cheque_status === \App\Enums\ChequeStatus::Cleared)
                        <x-ui.alert variant="success" class="mt-4" title="Cheque cleared">
                            Cleared funds confirmed by the clearing officer.
                        </x-ui.alert>
                    @else
                        <x-ui.alert variant="warning" class="mt-4" title="Awaiting clearance">
                            A cheque is not verified funds. It will only be available for batching after a clearing officer confirms the outcome.
                        </x-ui.alert>
                    @endif
                </x-ui.card>
            @endif

            @if ($payment->external_reference || $payment->settlement_date || $payment->terminal_id || $payment->bank)
                <x-ui.card title="Settlement details" subtitle="Channel-specific information captured at source">
                    <dl class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-2">
                        @if ($payment->settlement_date)
                            <div>
                                <dt class="text-slate-500">Settlement date</dt>
                                <dd class="mt-0.5 font-medium text-slate-800">{{ $payment->settlement_date->format('j M Y') }}</dd>
                            </div>
                        @endif
                        @if ($payment->terminal_id)
                            <div>
                                <dt class="text-slate-500">Terminal</dt>
                                <dd class="mt-0.5 font-mono font-medium text-slate-800">{{ $payment->terminal_id }}</dd>
                            </div>
                        @endif
                        @if ($payment->bank)
                            <div>
                                <dt class="text-slate-500">Bank</dt>
                                <dd class="mt-0.5 font-medium text-slate-800">{{ $payment->bank->name }}</dd>
                            </div>
                        @endif
                        @if ($payment->verified_at)
                            <div>
                                <dt class="text-slate-500">Verified</dt>
                                <dd class="mt-0.5 font-medium text-slate-800">
                                    {{ $payment->verified_at->format('j M Y H:i') }}
                                    @if ($payment->verifiedBy)
                                        <span class="text-slate-400"> · {{ $payment->verifiedBy->name }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>
                </x-ui.card>
            @endif

            @if ($payment->notes)
                <x-ui.card title="Internal notes">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $payment->notes }}</p>
                </x-ui.card>
            @endif

            @if ($payment->cancellation_reason || $payment->reversal_reason)
                <x-ui.card title="Cancellation & reversal">
                    <dl class="space-y-3 text-xs">
                        @if ($payment->cancellation_reason)
                            <div>
                                <dt class="font-semibold text-slate-700">Cancellation reason</dt>
                                <dd class="mt-1 text-slate-600">{{ $payment->cancellation_reason }}</dd>
                                <dd class="mt-1 text-[11px] text-slate-400">
                                    {{ $payment->cancelled_at?->format('j M Y H:i') }} · {{ $payment->cancelledBy?->name }}
                                </dd>
                            </div>
                        @endif
                        @if ($payment->reversal_reason)
                            <div>
                                <dt class="font-semibold text-slate-700">Reversal reason</dt>
                                <dd class="mt-1 text-slate-600">{{ $payment->reversal_reason }}</dd>
                                <dd class="mt-1 text-[11px] text-slate-400">{{ $payment->reversed_at?->format('j M Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-ui.card>
            @endif

            @if ($payment->adjustments->isNotEmpty())
                <x-ui.card title="Adjustments" subtitle="Reversal requests raised against this payment" padding="false">
                    <div class="divide-y divide-slate-100">
                        @foreach ($payment->adjustments->sortByDesc('created_at') as $adjustment)
                            <div class="px-5 py-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-slate-800">
                                            {{ $adjustment->type->label() }} · {{ \App\Support\Money::format($adjustment->amount, $payment->currency) }}
                                        </p>
                                        <p class="mt-1 text-[11px] text-slate-500">{{ $adjustment->reason }}</p>
                                        <p class="mt-1 text-[11px] text-slate-400">
                                            {{ $adjustment->requester?->name }} · {{ $adjustment->requested_at?->format('j M Y H:i') }}
                                        </p>
                                    </div>
                                    <x-ui.status-badge :status="$adjustment->status" />
                                </div>

                                @if ($adjustment->decision_notes)
                                    <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-[11px] text-slate-600">
                                        <span class="font-semibold">{{ $adjustment->decider?->name ?? 'Reviewer' }}:</span>
                                        {{ $adjustment->decision_notes }}
                                    </p>
                                @endif

                                @if ($canApproveReversal && $adjustment->status === \App\Enums\AdjustmentStatus::Pending)
                                    <div class="mt-3 space-y-2">
                                        <x-ui.textarea wire:model="decision_notes" name="decision_notes" rows="2" placeholder="Decision notes (required to reject)" />
                                        @error('decision_notes')
                                            <p class="text-[11px] font-medium text-rose-600">{{ $message }}</p>
                                        @enderror
                                        <div class="flex gap-2">
                                            <x-ui.button size="sm" variant="success" icon="check" wire:click="decideReversal({{ $adjustment->id }}, true)" wire:loading.attr="disabled">
                                                Approve reversal
                                            </x-ui.button>
                                            <x-ui.button size="sm" variant="danger" icon="x-mark" wire:click="decideReversal({{ $adjustment->id }}, false)" wire:loading.attr="disabled">
                                                Reject
                                            </x-ui.button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif

            <x-ui.card title="Audit trail" subtitle="Workflow transitions recorded for this payment" padding="false">
                @if ($transitions->isEmpty())
                    <x-ui.empty title="No transitions recorded" message="Workflow changes will be listed here." icon="clock" />
                @else
                    <ol class="divide-y divide-slate-100">
                        @foreach ($transitions as $transition)
                            <li class="flex items-start gap-3 px-5 py-3">
                                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                    <x-icon name="refresh" class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-700">
                                        {{ str($transition->from_status)->replace('_', ' ')->headline() ?: 'Created' }}
                                        <span class="text-slate-400">→</span>
                                        {{ str($transition->to_status)->replace('_', ' ')->headline() }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">
                                        {{ $transition->actor?->name ?? 'System' }}
                                        @if ($transition->reason)
                                            · {{ $transition->reason }}
                                        @endif
                                    </p>
                                </div>
                                <time class="text-[11px] text-slate-400">{{ $transition->created_at?->format('j M H:i') }}</time>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Receipt">
                @if ($payment->receipt)
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-mint-50 text-mint-700">
                            <x-icon name="receipt" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-semibold text-slate-800">{{ $payment->receipt->receipt_number }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">
                                Issued {{ $payment->receipt->issued_at?->format('j M Y H:i') }}
                            </p>
                            <p class="text-[11px] text-slate-500">
                                {{ $payment->receipt->reprint_count > 0 ? 'Reprinted '.$payment->receipt->reprint_count.' time(s)' : 'Original issue' }}
                            </p>
                        </div>
                    </div>
                    @if (auth()->user()->can('receipts.issue') || auth()->user()->can('receipts.reprint'))
                        <x-ui.button :href="route('payments.receipt', $payment)" variant="secondary" icon="print" class="mt-4 w-full">Print receipt</x-ui.button>
                    @endif
                @else
                    <p class="text-xs text-slate-500">No receipt has been issued for this payment.</p>
                @endif
            </x-ui.card>

            <x-ui.card title="Batch & lodgement">
                @if ($payment->currentBatch())
                    <p class="text-[11px] text-slate-500">Included in daily collection batch</p>
                    <a href="{{ route('collections.show', $payment->currentBatch()) }}" class="mt-1 block font-mono text-xs font-semibold text-navy-700 hover:text-navy-900">
                        {{ $payment->currentBatch()->batch_number }}
                    </a>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-[11px] text-slate-500">Batch status</span>
                        <x-ui.status-badge :status="$payment->currentBatch()->status" />
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-[11px] text-slate-500">Lodgement</span>
                        <span class="text-[11px] font-medium text-slate-700">{{ $payment->isDeposited() ? 'Banked' : 'Not yet banked' }}</span>
                    </div>
                @else
                    <p class="text-xs text-slate-500">This payment has not been included in a collection batch.</p>
                    @if ($payment->isBatchable())
                        <x-ui.alert variant="info" class="mt-3" title="Available for batching">
                            Eligible for the next daily collection batch.
                        </x-ui.alert>
                    @endif
                @endif
            </x-ui.card>

            @if ($canCancel)
                <x-ui.card title="Cancel payment" subtitle="Available before batching and verification">
                    <form wire:submit="cancel" class="space-y-3">
                        <x-ui.textarea wire:model="cancel_reason" name="cancel_reason" rows="3" label="Cancellation reason" required help="Minimum 5 characters. Retained permanently in the audit trail." />
                        <x-ui.button type="submit" variant="danger" class="w-full" icon="x-circle" wire:loading.attr="disabled">
                            Submit cancellation
                        </x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            @if ($canClearCheque)
                <x-ui.card title="Cheque clearance" subtitle="Record the bank outcome">
                    <form wire:submit="clearCheque" class="space-y-3">
                        <x-ui.select wire:model="cheque_decision" name="cheque_decision" label="Outcome" required>
                            <option value="cleared">Cleared — funds confirmed</option>
                            <option value="dishonoured">Dishonoured — funds not received</option>
                        </x-ui.select>
                        <x-ui.textarea wire:model="cheque_notes" name="cheque_notes" rows="3" label="Clearance notes" help="Bank advice reference or reason for dishonour." />
                        <x-ui.button type="submit" class="w-full" icon="scale" wire:loading.attr="disabled">Record outcome</x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            @if ($canReverse)
                <x-ui.card title="Request reversal" subtitle="Requires a second approver">
                    <form wire:submit="requestReversal" class="space-y-3">
                        <x-ui.textarea wire:model="reversal_reason" name="reversal_reason" rows="3" label="Reason for reversal" required help="Minimum 10 characters." />
                        <x-ui.button type="submit" variant="secondary" class="w-full" icon="refresh" wire:loading.attr="disabled">
                            Submit reversal request
                        </x-ui.button>
                    </form>
                </x-ui.card>
            @endif

            <x-ui.alert variant="info" title="Financial control">
                Status changes are validated server-side. Every action on this payment is attributed to your account and cannot be deleted.
            </x-ui.alert>
        </div>
    </div>
</div>
