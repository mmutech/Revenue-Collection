<div class="space-y-6">
    <x-ui.page-header title="Create treatment request" subtitle="Submit a request for review within your authorised branch scope." :back="route('treatment.index')" />

    <x-ui.card title="Request details">
        <form wire:submit="save" class="space-y-4">
            <x-ui.select wire:model="account_id" name="account_id" label="Account" required>
                <option value="">Select an account</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->account_number }} — {{ $account->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select wire:model.live="account_treatment_type_id" name="account_treatment_type_id" label="Treatment type" required>
                <option value="">Select a treatment type</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select wire:model="account_treatment_reason_id" name="account_treatment_reason_id" label="Reason">
                <option value="">Select a reason (optional)</option>
                @foreach ($reasons as $reason)
                    <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                @endforeach
            </x-ui.select>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input wire:model="amount" name="amount" type="number" step="0.01" min="0.01" label="Amount (optional)" />
                <x-ui.input wire:model="effective_date" name="effective_date" type="date" label="Effective date (optional)" />
            </div>

            <x-ui.textarea wire:model="description" name="description" label="Description" rows="4" required />

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('treatment.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Create request</span>
                    <span wire:loading wire:target="save">Creating…</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
