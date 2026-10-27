@if ($referenceType === 'channels')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.select wire:model="form.code" name="form.code" label="Payment channel" placeholder="Select a payment channel" required>
            @foreach ($channelCodes as $channelCode)
                <option value="{{ $channelCode->value }}">{{ $channelCode->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input wire:model="form.name" name="form.name" label="Name" required />
        <x-ui.textarea wire:model="form.description" name="form.description" label="Description" rows="2" class="sm:col-span-2" />
        <x-ui.input wire:model="form.sort_order" name="form.sort_order" type="number" min="0" label="Sort order" required />
        <x-ui.textarea wire:model="form.required_fields" name="form.required_fields" label="Required fields (JSON array)" rows="2" placeholder='["external_reference"]' class="sm:col-span-2" />
        <x-ui.textarea wire:model="form.settings_json" name="form.settings_json" label="Channel settings (JSON object)" rows="2" placeholder="{}" class="sm:col-span-2" />
    </div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (['is_active' => 'Channel active', 'requires_receipt' => 'Requires receipt', 'requires_external_reference' => 'Requires external reference', 'requires_bank' => 'Requires bank', 'requires_bank_branch' => 'Requires bank branch', 'requires_terminal' => 'Requires terminal', 'requires_cheque_details' => 'Requires cheque details', 'requires_settlement_date' => 'Requires settlement date'] as $field => $label)
            <x-ui.checkbox wire:model="form.{{ $field }}" name="form.{{ $field }}" :label="$label" wire:key="channel-option-{{ $field }}" />
        @endforeach
    </div>
@elseif ($referenceType === 'banks')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.input wire:model="form.code" name="form.code" label="Code" required />
        <x-ui.input wire:model="form.name" name="form.name" label="Name" required />
        <x-ui.input wire:model="form.short_name" name="form.short_name" label="Short name" />
    </div>
    <x-ui.checkbox wire:model="form.is_active" name="form.is_active" label="Bank active" />
@elseif ($referenceType === 'bank-branches')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.select wire:model="form.bank_id" name="form.bank_id" label="Bank" placeholder="Select a bank" required>
            @foreach ($banks as $bank)
                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input wire:model="form.code" name="form.code" label="Code" required />
        <x-ui.input wire:model="form.name" name="form.name" label="Name" required />
        <x-ui.input wire:model="form.sort_code" name="form.sort_code" label="Sort code" />
        <x-ui.input wire:model="form.address" name="form.address" label="Address" class="sm:col-span-2" />
    </div>
    <x-ui.checkbox wire:model="form.is_active" name="form.is_active" label="Bank branch active" />
@elseif ($referenceType === 'collection-points')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.select wire:model="form.branch_id" name="form.branch_id" label="Branch" placeholder="Select a branch" required>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input wire:model="form.code" name="form.code" label="Code" required />
        <x-ui.input wire:model="form.name" name="form.name" label="Name" required />
        <x-ui.input wire:model="form.type" name="form.type" label="Type" required />
    </div>
    <x-ui.checkbox wire:model="form.is_active" name="form.is_active" label="Collection point active" />
@elseif ($referenceType === 'treatment-types')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.input wire:model="form.code" name="form.code" label="Code" required />
        <x-ui.input wire:model="form.name" name="form.name" label="Name" required />
        <x-ui.select wire:model="form.approval_workflow_id" name="form.approval_workflow_id" label="Approval workflow" placeholder="No workflow">
            @foreach ($workflows as $workflow)
                <option value="{{ $workflow->id }}">{{ $workflow->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input wire:model="form.sort_order" name="form.sort_order" type="number" min="0" label="Display order" required />
        <x-ui.textarea wire:model="form.description" name="form.description" label="Description" rows="2" class="sm:col-span-2" />
        <x-ui.input wire:model="form.required_document_types" name="form.required_document_types" label="Required document types" help="Separate document types with commas." class="sm:col-span-2" />
    </div>
    <div class="flex flex-wrap gap-5">
        <x-ui.checkbox wire:model="form.requires_approval" name="form.requires_approval" label="Requires approval" />
        <x-ui.checkbox wire:model="form.is_active" name="form.is_active" label="Treatment type active" />
    </div>
@elseif ($referenceType === 'treatment-reasons')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.select wire:model="form.account_treatment_type_id" name="form.account_treatment_type_id" label="Treatment type" placeholder="Select a treatment type" required>
            @foreach ($treatmentTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input wire:model="form.code" name="form.code" label="Code" required />
        <x-ui.input wire:model="form.name" name="form.name" label="Name" required />
        <x-ui.textarea wire:model="form.description" name="form.description" label="Description" rows="2" class="sm:col-span-2" />
    </div>
    <x-ui.checkbox wire:model="form.is_active" name="form.is_active" label="Treatment reason active" />
@endif
