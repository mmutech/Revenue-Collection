<div class="space-y-6">
    <x-ui.page-header :title="$section['title']" :subtitle="$section['description']">
        @if ($sectionKey !== 'admin.reference' || auth()->user()->can($referenceTypes[$referenceType]['permission']))
            <x-slot:actions>
                <x-ui.button wire:click="createRecord" icon="plus">
                    Add {{ $sectionKey === 'admin.reference' ? str($referenceTypes[$referenceType]['title'])->singular()->lower() : str($section['title'])->singular()->lower() }}
                </x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    @if (session()->has('status'))
        <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($sectionKey === 'admin.reference')
        <x-ui.card padding="false">
            <nav aria-label="Reference data types" class="flex flex-wrap gap-2 p-4">
                @foreach ($referenceTypes as $key => $type)
                    @if (auth()->user()->can($type['permission']))
                        <x-ui.button
                            wire:click="$set('referenceType', '{{ $key }}')"
                            :variant="$referenceType === $key ? 'primary' : 'secondary'"
                            size="sm"
                            wire:key="reference-type-{{ $key }}"
                        >{{ $type['title'] }}</x-ui.button>
                    @endif
                @endforeach
            </nav>
        </x-ui.card>
    @endif

    @if ($showForm)
        <x-ui.card :title="$editingId ? 'Edit record' : 'Create record'" subtitle="Enter the details below, then save your changes.">
            <form wire:submit="saveRecord" class="space-y-5">
                @if ($sectionKey === 'admin.users')
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input wire:model="form.name" name="form.name" label="Name" required />
                        <x-ui.input wire:model="form.email" name="form.email" type="email" label="Email" required />
                        <x-ui.input wire:model="form.employee_no" name="form.employee_no" label="Employee number" />
                        <x-ui.input wire:model="form.phone" name="form.phone" label="Phone" />
                        <x-ui.input wire:model="form.job_title" name="form.job_title" label="Job title" />
                        <x-ui.input
                            wire:model="form.password"
                            name="form.password"
                            type="password"
                            label="Temporary password"
                            :required="! $editingId"
                            autocomplete="new-password"
                            :help="$editingId ? 'Leave blank to keep the current password.' : 'Use at least 12 characters.'"
                        />
                        <x-ui.select wire:model="form.branch_id" name="form.branch_id" label="Primary branch" placeholder="No primary branch">
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.select wire:model="form.collection_point_id" name="form.collection_point_id" label="Collection point" placeholder="No collection point">
                            @foreach ($collectionPoints as $point)
                                <option value="{{ $point->id }}">{{ $point->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <fieldset class="space-y-2">
                        <legend class="text-xs font-semibold text-slate-700">Additional branch access</legend>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($branches as $branch)
                                <label wire:key="user-branch-{{ $branch->id }}" class="flex items-center gap-2 text-sm text-slate-600">
                                    <input type="checkbox" wire:model="form.branch_ids" value="{{ $branch->id }}" class="h-4 w-4 rounded border-slate-300 text-navy-700 focus:ring-navy-500">
                                    {{ $branch->name }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <fieldset class="space-y-2">
                        <legend class="text-xs font-semibold text-slate-700">Roles</legend>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($roles as $role)
                                <label wire:key="user-role-{{ $role->id }}" class="flex items-center gap-2 text-sm text-slate-600">
                                    <input type="checkbox" wire:model="form.role_ids" value="{{ $role->id }}" class="h-4 w-4 rounded border-slate-300 text-navy-700 focus:ring-navy-500">
                                    {{ $role->name }}
                                </label>
                            @endforeach
                        </div>
                        @error('form.role_ids') <p class="text-[11px] font-medium text-rose-600">{{ $message }}</p> @enderror
                    </fieldset>
                    <x-ui.checkbox wire:model="form.is_active" name="form.is_active" label="Account active" />

                @elseif ($sectionKey === 'admin.branches')
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input wire:model="form.code" name="form.code" label="Code" required />
                        <x-ui.input wire:model="form.name" name="form.name" label="Name" required />
                        <x-ui.input wire:model="form.address" name="form.address" label="Address" />
                        <x-ui.input wire:model="form.phone" name="form.phone" label="Phone" />
                        <x-ui.input wire:model="form.email" name="form.email" type="email" label="Email" />
                    </div>
                    <x-ui.checkbox wire:model="form.is_active" name="form.is_active" label="Branch active" />

                @elseif ($sectionKey === 'admin.roles')
                    <x-ui.input wire:model="form.name" name="form.name" label="Role name" required />
                    <fieldset class="space-y-4">
                        <legend class="text-xs font-semibold text-slate-700">Registered permissions</legend>
                        @foreach ($permissionGroups as $group => $permissions)
                            <div wire:key="permission-group-{{ str($group)->slug() }}" class="rounded-lg border border-slate-200 p-4">
                                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $group }}</h3>
                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($permissions as $permission => $label)
                                        <label wire:key="permission-{{ $permission }}" class="flex items-start gap-2 text-sm text-slate-600">
                                            <input type="checkbox" wire:model="form.permission_names" value="{{ $permission }}" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-navy-700 focus:ring-navy-500">
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </fieldset>

                @elseif ($sectionKey === 'admin.settings')
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input wire:model="form.key" name="form.key" label="Key" required />
                        <x-ui.input wire:model="form.group" name="form.group" label="Group" required />
                        <x-ui.input wire:model="form.label" name="form.label" label="Label" required />
                        <x-ui.select wire:model="form.type" name="form.type" label="Type" required>
                            @foreach (['string', 'boolean', 'integer', 'number', 'json'] as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.textarea wire:model="form.description" name="form.description" label="Description" rows="2" class="sm:col-span-2" />
                        <x-ui.textarea
                            wire:model="form.value"
                            name="form.value"
                            label="Value"
                            rows="3"
                            class="sm:col-span-2"
                            :help="$editingId ? 'Private values are masked. Leave blank to keep the current value.' : 'Enter a value matching the selected type.'"
                        />
                    </div>
                    <div class="flex flex-wrap gap-5">
                        <x-ui.checkbox wire:model="form.is_public" name="form.is_public" label="Value is public" />
                        @if ($editingId)
                            <x-ui.checkbox wire:model="form.clear_value" name="form.clear_value" label="Clear the existing value" />
                        @endif
                    </div>

                @elseif ($sectionKey === 'admin.workflows')
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input wire:model="form.code" name="form.code" label="Code" required />
                        <x-ui.input wire:model="form.name" name="form.name" label="Name" required />
                        <x-ui.select wire:model="form.subject_type" name="form.subject_type" label="Subject" required>
                            <option value="App\Models\AccountTreatmentRequest">Account treatment request</option>
                            <option value="App\Models\Payment">Payment</option>
                        </x-ui.select>
                        <div class="flex items-end">
                            <x-ui.checkbox wire:model="form.is_active" name="form.is_active" label="Workflow active" />
                        </div>
                        <x-ui.textarea wire:model="form.description" name="form.description" label="Description" rows="2" class="sm:col-span-2" />
                        <x-ui.textarea wire:model="form.conditions_json" name="form.conditions_json" label="Workflow conditions (JSON)" rows="3" placeholder="{}" class="sm:col-span-2" />
                    </div>
                    <fieldset class="space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <legend class="text-xs font-semibold text-slate-700">Approval stages</legend>
                            <x-ui.button type="button" wire:click="addWorkflowStage" variant="secondary" size="sm" icon="plus">Add stage</x-ui.button>
                        </div>
                        @error('form.stages') <x-ui.alert variant="error">{{ $message }}</x-ui.alert> @enderror
                        @foreach ($form['stages'] ?? [] as $index => $stage)
                            <div wire:key="workflow-stage-{{ $stage['id'] ?? 'new-'.$index }}" class="space-y-4 rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-xs font-semibold text-slate-700">Stage {{ $index + 1 }}</h3>
                                    <x-ui.button type="button" wire:click="removeWorkflowStage({{ $index }})" variant="ghost" size="sm">Remove</x-ui.button>
                                </div>
                                <input type="hidden" wire:model="form.stages.{{ $index }}.id">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <x-ui.input wire:model="form.stages.{{ $index }}.sequence" name="form.stages.{{ $index }}.sequence" type="number" min="1" label="Sequence" required />
                                    <x-ui.input wire:model="form.stages.{{ $index }}.name" name="form.stages.{{ $index }}.name" label="Stage name" required />
                                    <x-ui.select wire:model="form.stages.{{ $index }}.approver_role" name="form.stages.{{ $index }}.approver_role" label="Approver role" placeholder="Select a role" required>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                                        @endforeach
                                    </x-ui.select>
                                    <x-ui.select wire:model="form.stages.{{ $index }}.permission" name="form.stages.{{ $index }}.permission" label="Permission (optional)" placeholder="No extra permission">
                                        @foreach ($permissionGroups as $permissions)
                                            @foreach ($permissions as $permission => $label)
                                                <option value="{{ $permission }}">{{ $label }}</option>
                                            @endforeach
                                        @endforeach
                                    </x-ui.select>
                                    <x-ui.input wire:model="form.stages.{{ $index }}.required_approvals" name="form.stages.{{ $index }}.required_approvals" type="number" min="1" label="Required approvals" required />
                                    <div class="flex items-end">
                                        <x-ui.checkbox wire:model="form.stages.{{ $index }}.is_final" name="form.stages.{{ $index }}.is_final" label="Final stage" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </fieldset>

                @elseif ($sectionKey === 'admin.reference')
                    @include('pages.admin.⚡index.reference-form', ['referenceType' => $referenceType])
                @endif

                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
                    <x-ui.button type="button" wire:click="closeForm" variant="secondary">Cancel</x-ui.button>
                    <x-ui.button type="submit" wire:loading.attr="disabled" icon="check">
                        <span wire:loading.remove wire:target="saveRecord">Save changes</span>
                        <span wire:loading wire:target="saveRecord">Saving…</span>
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card :title="$sectionKey === 'admin.reference' ? $referenceTypes[$referenceType]['title'] : $section['title']" padding="false">
        @if ($records->isEmpty())
            <x-ui.empty title="No records configured" message="Create the first record in this section." icon="document" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="table-head">
                        <tr>
                            <th class="table-cell">Record</th>
                            <th class="table-cell">Details</th>
                            <th class="table-cell">Status</th>
                            <th class="table-cell text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            <tr wire:key="admin-record-{{ $record['id'] }}">
                                <td class="table-cell min-w-40 font-medium text-slate-800">
                                    @if (isset($record['channel']))
                                        <div class="flex items-center gap-2">
                                            <x-ui.channel-badge :channel="$record['channel']" />
                                            <span>{{ $record['label'] }}</span>
                                        </div>
                                    @else
                                        {{ $record['label'] }}
                                    @endif
                                </td>
                                <td class="table-cell text-xs text-slate-500">{{ $record['detail'] }}</td>
                                <td class="table-cell">
                                    @if ($record['status'] === 'Active')
                                        <x-ui.badge color="emerald" dot>Active</x-ui.badge>
                                    @elseif ($record['status'] === 'Inactive')
                                        <x-ui.badge color="slate" dot>Inactive</x-ui.badge>
                                    @elseif ($record['status'] === 'Protected Administrator')
                                        <x-ui.badge color="navy">{{ $record['status'] }}</x-ui.badge>
                                    @elseif ($record['status'] === 'Built-in role')
                                        <x-ui.badge color="blue">{{ $record['status'] }}</x-ui.badge>
                                    @else
                                        <x-ui.badge color="slate">{{ $record['status'] }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="table-cell text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($sectionKey !== 'admin.roles' || $record['status'] !== 'Protected Administrator')
                                            <x-ui.button wire:click="editRecord({{ $record['id'] }})" variant="secondary" size="sm" icon="pencil">Edit</x-ui.button>
                                        @endif
                                        @if ($sectionKey !== 'admin.roles' || $record['status'] === 'Custom role')
                                            <x-ui.button
                                                wire:click="deleteRecord({{ $record['id'] }})"
                                                wire:confirm="{{ $sectionKey === 'admin.users' ? 'Deactivate this user account?' : 'Delete this record? Records already referenced by history will be deactivated instead.' }}"
                                                variant="danger"
                                                size="sm"
                                            >{{ $record['action'] }}</x-ui.button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $records->links() }}</div>
        @endif
    </x-ui.card>
</div>
