
@php($channel = $this->channel)
<div class="space-y-5">
    <x-ui.page-header title="Record payment" subtitle="Capture a collection against the correct channel. Server-side controls validate every requirement." back="{{ route('payments.index') }}" />

    @error('form')
        <x-ui.alert variant="error" title="Payment could not be recorded">{{ $message }}</x-ui.alert>
    @enderror

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Payment details" subtitle="Core transaction information">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.select wire:model.live="branch_id" name="branch_id" label="Branch" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->code }} — {{ $branch->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select wire:model.live="channel_id" name="channel_id" label="Payment channel" required>
                        @foreach ($channels as $channelOption)
                            <option value="{{ $channelOption->id }}">{{ $channelOption->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.input wire:model="amount" name="amount" type="number" step="0.01" min="0.01" label="Amount" required icon="banknotes" />
                    <x-ui.input wire:model="currency" name="currency" label="Currency" maxlength="3" required help="ISO 4217 code, e.g. NGN" />

                    <x-ui.input wire:model="payment_date" name="payment_date" type="date" label="Payment date" required :max="today()->toDateString()" />
                    <x-ui.input wire:model="payer_phone" name="payer_phone" label="Payer phone" />

                    <x-ui.input wire:model="payer_name" name="payer_name" label="Payer name" required class="sm:col-span-2" />
                    <x-ui.input wire:model="payer_email" name="payer_email" type="email" label="Payer email" class="sm:col-span-2" />
                </div>
            </x-ui.card>

            @if ($channel)
                <x-ui.card title="{{ $channel->name }} details" subtitle="Fields required by the selected channel">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @if ($channel->requires_external_reference)
                            <x-ui.input wire:model="external_reference" name="external_reference" label="External transaction reference" required help="Unique reference from the channel or provider" />
                        @endif

                        @if ($channel->code === 'aes')
                            <x-ui.input wire:model="provider" name="provider" label="Settlement provider" placeholder="e.g. AES Clearing" />
                        @endif

                        @if ($channel->requires_settlement_date)
                            <x-ui.input wire:model="settlement_date" name="settlement_date" type="date" label="Expected settlement date" required />
                        @endif

                        @if ($channel->requires_terminal)
                            <x-ui.input wire:model="terminal_id" name="terminal_id" label="Terminal identifier" required />
                        @endif

                        @if ($channel->requires_bank)
                            <x-ui.select wire:model.live="bank_id" name="bank_id" label="Bank" placeholder="Select bank" required>
                                @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </x-ui.select>
                        @endif

                        @if ($channel->requires_bank_branch)
                            <x-ui.select wire:model="bank_branch_id" name="bank_branch_id" label="Bank branch" placeholder="Select branch" required>
                                @foreach ($this->bankBranches as $bankBranch)
                                    <option value="{{ $bankBranch->id }}">{{ $bankBranch->name }}</option>
                                @endforeach
                            </x-ui.select>
                        @endif

                        @if ($channel->requires_cheque_details)
                            <x-ui.input wire:model="cheque_number" name="cheque_number" label="Cheque number" required />
                            <x-ui.input wire:model="cheque_date" name="cheque_date" type="date" label="Cheque date" required />
                            <x-ui.input wire:model="cheque_drawer" name="cheque_drawer" label="Drawer name" required class="sm:col-span-2" />
                        @endif
                    </div>

                    @if ($channel->code === 'cheque')
                        <x-ui.alert variant="warning" class="mt-4" title="Cheques are not verified funds">
                            A cheque remains pending clearance and cannot be added to a collection batch until a clearing officer records clearance.
                        </x-ui.alert>
                    @endif
                </x-ui.card>
            @endif

            <x-ui.card title="Notes" subtitle="Optional internal context">
                <x-ui.textarea wire:model="notes" name="notes" rows="3" label="Internal notes" help="Do not record card numbers or full bank account details." />
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Transaction summary">
                <dl class="space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Channel</dt>
                        <dd class="font-semibold text-slate-800">{{ $channel?->name ?? 'Not selected' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Receipt</dt>
                        <dd class="font-semibold text-slate-800">{{ $channel?->requires_receipt || $channel?->isCash() ? 'Will be issued' : 'Not required' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Amount</dt>
                        <dd class="tabular text-base font-semibold text-slate-950">{{ \App\Support\Money::format($amount ?: 0, $currency ?: 'NGN') }}</dd>
                    </div>
                </dl>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <x-ui.button type="submit" class="w-full" icon="check" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Record payment</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </x-ui.button>
                    <x-ui.button :href="route('payments.index')" variant="secondary" class="mt-2 w-full">Cancel</x-ui.button>
                </div>
            </x-ui.card>

            @if ($channel?->isCash() && ! $openShift)
                <x-ui.alert variant="warning" title="Open a shift first">
                    Cash payments must be attached to an open cashier shift.
                    <a href="{{ route('shifts.index') }}" class="font-semibold underline decoration-amber-400 underline-offset-2">Open a shift</a>.
                </x-ui.alert>
            @elseif ($openShift)
                <x-ui.alert variant="success" title="Shift active">
                    {{ $openShift->collectionPoint?->name ?? 'Cash desk' }} · float {{ \App\Support\Money::format($openShift->opening_float) }}
                </x-ui.alert>
            @endif

            <x-ui.alert variant="info" title="Financial control">
                Amounts, duplicate references and channel requirements are validated on the server. This action is attributed to you and retained in the audit trail.
            </x-ui.alert>
        </div>
    </form>
</div>