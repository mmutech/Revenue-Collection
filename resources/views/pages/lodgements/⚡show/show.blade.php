@php($lodgement = $this->lodgement)
<div class="space-y-6">
    <x-ui.page-header title="Lodgement {{ $lodgement->lodgement_number }}" subtitle="Bank deposit, supporting evidence and treasury confirmation." back="{{ route('lodgements.index') }}">
        <x-slot:actions>
            @if ($canConfirm)
                <x-ui.button :href="route('treasury.show', $lodgement)" icon="shield">Open treasury review</x-ui.button>
            @endif
            <x-ui.status-badge :status="$lodgement->status" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($action_error)
        <x-ui.alert variant="error" title="Action blocked">{{ $action_error }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Deposit amount" :value="\App\Support\Money::compact($lodgement->amount)" :hint="$lodgement->currency.' · slip '.$lodgement->deposit_slip_number" icon="bank" color="mint" />
        <x-ui.stat label="Batches allocated" :value="$lodgement->items->count()" icon="clipboard" color="navy" />
        <x-ui.stat label="Evidence files" :value="$lodgement->documents->count()" icon="document" color="blue" />
        <x-ui.stat
            label="Treasury variance"
            :value="\App\Support\Money::compact((float) ($lodgement->latestTreasuryVerification?->variance ?? 0))"
            :hint="$lodgement->latestTreasuryVerification?->decision ? \App\Enums\TreasuryDecision::tryFrom($lodgement->latestTreasuryVerification->decision)?->label() : 'Not yet verified'"
            icon="scale"
            :color="abs((float) ($lodgement->latestTreasuryVerification?->variance ?? 0)) < 0.01 ? 'mint' : 'rose'"
        />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Deposit details">
                <dl class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-slate-500">Bank</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->bank?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Bank branch</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->bankBranch?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Branch</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->branch?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Deposit date</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->deposit_date?->format('j M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Deposited by</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->deposited_by_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Created by</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Submitted</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->submitted_at?->format('j M Y H:i') ?? 'Not submitted' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Treasury confirmed</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $lodgement->treasury_verified_at?->format('j M Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Bank reference</dt>
                        <dd class="mt-0.5 font-mono font-medium text-slate-800">{{ $lodgement->bank_confirmation_reference ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($lodgement->notes)
                    <p class="mt-4 whitespace-pre-line rounded-lg bg-slate-50 px-3.5 py-3 text-xs text-slate-600">{{ $lodgement->notes }}</p>
                @endif

                @if ($lodgement->decision_notes)
                    <x-ui.alert variant="warning" class="mt-4" title="Treasury decision notes">{{ $lodgement->decision_notes }}</x-ui.alert>
                @endif
            </x-ui.card>

            <x-ui.card title="Allocated batches" :subtitle="$lodgement->items->count().' batches'" padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3 text-left">Batch</th>
                                <th class="px-4 py-3 text-left">Cashier</th>
                                <th class="px-4 py-3 text-left">Business date</th>
                                <th class="px-4 py-3 text-right">Allocated</th>
                                <th class="px-4 py-3 text-left">Status</th>
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
                                    <td class="table-cell text-right"><x-ui.money :amount="$item->amount" class="font-semibold text-slate-900" /></td>
                                    <td class="table-cell"><x-ui.status-badge :status="$item->batch?->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-slate-200 bg-slate-50/60">
                            <tr>
                                <td colspan="3" class="px-5 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total allocated</td>
                                <td class="px-4 py-2.5 text-right"><x-ui.money :amount="$lodgement->items->sum('amount')" class="text-sm font-semibold text-slate-950" /></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-ui.card>

            <x-ui.card title="Supporting evidence" subtitle="Stamped deposit slip or bank advice, stored privately" padding="false">
                @if ($lodgement->documents->isEmpty())
                    <x-ui.empty title="No evidence attached" message="Upload the stamped deposit slip before submitting to treasury." icon="document" />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($lodgement->documents as $document)
                            <div class="flex items-center gap-3 px-5 py-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                    <x-icon name="document" class="h-4 w-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-slate-800">{{ $document->original_name }}</p>
                                    <p class="text-[10px] text-slate-400">
                                        {{ strtoupper($document->mime_type ?? 'file') }} · {{ number_format(((int) $document->size) / 1024, 1) }} KB · {{ $document->created_at?->format('j M Y H:i') }}
                                    </p>
                                </div>
                                @if ($canEditEvidence)
                                    <button type="button" wire:click="deleteDocument({{ $document->id }})" class="text-rose-500 hover:text-rose-700" aria-label="Remove evidence">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            @if ($lodgement->treasuryVerifications->isNotEmpty())
                <x-ui.card title="Treasury decisions" padding="false">
                    <ol class="divide-y divide-slate-100">
                        @foreach ($lodgement->treasuryVerifications as $verification)
                            <li class="flex items-start gap-3 px-5 py-3">
                                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                    <x-icon name="shield" class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-700">
                                        {{ \App\Enums\TreasuryDecision::tryFrom($verification->decision)?->label() ?? $verification->decision }}
                                        · deposited {{ \App\Support\Money::format($verification->deposited_amount) }}
                                        · variance {{ \App\Support\Money::format($verification->variance) }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">{{ $verification->officer?->name }} · {{ $verification->verified_at?->format('j M Y H:i') }}</p>
                                    @if ($verification->notes)
                                        <p class="mt-0.5 text-[11px] text-slate-500">{{ $verification->notes }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </x-ui.card>
            @endif
        </div>

        <div class="space-y-6">
            @if ($canEditEvidence)
                <x-ui.card title="Attach evidence" subtitle="PDF, JPG or PNG up to 5 MB">
                    <form wire:submit="uploadDocument" class="space-y-3">
                        <x-ui.select wire:model="document_type" name="document_type" label="Document type" required>
                            <option value="deposit_slip">Deposit slip</option>
                            <option value="bank_advice">Bank advice</option>
                            <option value="teller_stamp">Teller stamp</option>
                            <option value="other">Other</option>
                        </x-ui.select>
                        <x-ui.input wire:model="document" name="document" type="file" label="File" required />
                        <div wire:loading wire:target="document" class="text-[11px] text-slate-500">Uploading…</div>
                        <x-ui.button type="submit" class="w-full" icon="upload" wire:loading.attr="disabled">Upload evidence</x-ui.button>
                    </form>
                </x-ui.card>

                @if ($canSubmit)
                    <x-ui.card title="Submit to treasury" subtitle="Locks the lodgement for review">
                        <p class="text-xs leading-relaxed text-slate-600">
                            Submitting transfers control to treasury. The creator and submitter cannot confirm the deposit themselves.
                        </p>
                        <x-ui.button wire:click="submit" class="mt-4 w-full" icon="paper-airplane" wire:loading.attr="disabled">
                            Submit lodgement
                        </x-ui.button>
                    </x-ui.card>
                @endif
            @else
                <x-ui.alert variant="info" title="Evidence is locked">
                    Evidence can only be added or removed while the lodgement is a draft or has been returned by treasury.
                </x-ui.alert>
            @endif

            @if ($lodgement->status === \App\Enums\LodgementStatus::UnderTreasuryReview)
                <x-ui.alert variant="warning" title="Awaiting treasury confirmation">
                    This lodgement is locked. Treasury officers verify the deposit against the bank statement before funds are recognised.
                </x-ui.alert>
            @endif

            @if ($lodgement->discrepancies->isNotEmpty())
                <x-ui.card title="Linked discrepancies" padding="false">
                    <div class="divide-y divide-slate-100">
                        @foreach ($lodgement->discrepancies as $discrepancy)
                            <a href="{{ route('discrepancies.show', $discrepancy) }}" class="flex items-center gap-3 px-5 py-3 transition hover:bg-slate-50">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                                    <x-icon name="alert" class="h-4 w-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-mono text-[11px] font-semibold text-slate-800">{{ $discrepancy->reference }}</p>
                                    <p class="truncate text-[10px] text-slate-500">{{ $discrepancy->type->label() }}</p>
                                </div>
                                <x-ui.status-badge :status="$discrepancy->status" />
                            </a>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif
        </div>
    </div>
</div>
