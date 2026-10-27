<?php

use App\Enums\PaymentChannelCode;
use App\Models\AccountTreatmentReason;
use App\Models\AccountTreatmentRequest;
use App\Models\AccountTreatmentType;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStage;
use App\Models\Bank;
use App\Models\BankBranch;
use App\Models\Branch;
use App\Models\CollectionPoint;
use App\Models\Payment;
use App\Models\PaymentChannel;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\PermissionRegistry;
use App\Support\RoleRegistry;
use App\Support\Setting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

new #[Layout('components.layouts.app')]
#[Title('Administration')] class extends Component
{
    use WithPagination;

    #[Locked]
    public string $sectionKey = '';

    public string $referenceType = 'channels';

    public bool $showForm = false;

    public ?int $editingId = null;

    /**
     * @var array<string, mixed>
     */
    public array $form = [];

    /**
     * @var array<string, array{title: string, permission: string, description: string}>
     */
    private const SECTIONS = [
        'admin.users' => [
            'title' => 'Users',
            'permission' => 'admin.users',
            'description' => 'Manage user accounts, branch assignments, roles, and active status.',
        ],
        'admin.branches' => [
            'title' => 'Branches',
            'permission' => 'admin.branches',
            'description' => 'Manage branches and their collection points.',
        ],
        'admin.reference' => [
            'title' => 'Reference data',
            'permission' => 'admin.channels',
            'description' => 'Manage payment, banking, collection, and treatment reference data.',
        ],
        'admin.workflows' => [
            'title' => 'Approval workflows',
            'permission' => 'admin.workflows',
            'description' => 'Manage approval workflows and their approval stages.',
        ],
        'admin.roles' => [
            'title' => 'Roles and permissions',
            'permission' => 'admin.roles',
            'description' => 'Manage custom roles and assign registered application permissions.',
        ],
        'admin.settings' => [
            'title' => 'System settings',
            'permission' => 'admin.settings',
            'description' => 'Manage typed system settings. Private values are masked and excluded from audit logs.',
        ],
    ];

    /**
     * @var array<string, array{title: string, permission: string}>
     */
    private const REFERENCE_TYPES = [
        'channels' => ['title' => 'Payment channels', 'permission' => 'admin.channels'],
        'banks' => ['title' => 'Banks', 'permission' => 'admin.banks'],
        'bank-branches' => ['title' => 'Bank branches', 'permission' => 'admin.banks'],
        'collection-points' => ['title' => 'Collection points', 'permission' => 'admin.branches'],
        'treatment-types' => ['title' => 'Treatment types', 'permission' => 'treatment.manage'],
        'treatment-reasons' => ['title' => 'Treatment reasons', 'permission' => 'treatment.manage'],
    ];

    public function mount(?string $section = null): void
    {
        $this->sectionKey = $section ?? (string) request()->route()?->getName();
        abort_unless(isset(self::SECTIONS[$this->sectionKey]), 404);
        $this->authorizeSection();

        if ($this->sectionKey === 'admin.reference' && ! auth()->user()->can(self::REFERENCE_TYPES[$this->referenceType]['permission'])) {
            $this->referenceType = collect(self::REFERENCE_TYPES)
                ->search(fn (array $reference): bool => auth()->user()->can($reference['permission'])) ?: '';
        }

        $this->authorizeReferenceType();
    }

    private function decodeOptionalJson(?string $value, string $field): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            throw ValidationException::withMessages([$field => 'Enter a valid JSON array or object.']);
        }

        return $decoded;
    }

    /**
     * @return array{id: null, sequence: int, name: string, approver_role: string, permission: string, required_approvals: int, is_final: bool}
     */
    private function defaultWorkflowStage(): array
    {
        return [
            'id' => null,
            'sequence' => 1,
            'name' => '',
            'approver_role' => '',
            'permission' => '',
            'required_approvals' => 1,
            'is_final' => true,
        ];
    }

    public function render(): View
    {
        $this->authorizeSection();
        $this->authorizeReferenceType();

        return view('pages.admin.⚡index.index', [
            'section' => self::SECTIONS[$this->sectionKey],
            'records' => $this->records(),
            'referenceTypes' => self::REFERENCE_TYPES,
            'permissionGroups' => PermissionRegistry::groups(),
            'channelCodes' => PaymentChannelCode::cases(),
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']),
            'branches' => in_array($this->sectionKey, ['admin.users', 'admin.reference', 'admin.branches'], true)
                ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'collectionPoints' => $this->sectionKey === 'admin.users'
                ? CollectionPoint::query()->where('is_active', true)->orderBy('name')->get(['id', 'branch_id', 'name'])
                : collect(),
            'workflows' => $this->sectionKey === 'admin.reference' && $this->referenceType === 'treatment-types'
                ? ApprovalWorkflow::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'treatmentTypes' => $this->sectionKey === 'admin.reference' && $this->referenceType === 'treatment-reasons'
                ? AccountTreatmentType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'banks' => $this->sectionKey === 'admin.reference' && $this->referenceType === 'bank-branches'
                ? Bank::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function updatedReferenceType(): void
    {
        $this->authorizeSection();
        $this->authorizeReferenceType();
        $this->resetPage();
        $this->closeForm();
    }

    public function createRecord(): void
    {
        $this->authorizeSection();
        $this->authorizeReferenceType();

        $this->editingId = null;
        $this->form = $this->defaultForm();
        $this->showForm = true;
    }

    public function editRecord(int $id): void
    {
        $this->authorizeSection();
        $this->authorizeReferenceType();

        $record = $this->findRecord($id);
        abort_unless($record, 404);

        if ($record instanceof Role && $this->isProtectedRole($record)) {
            abort(403, 'The built-in Administrator role cannot be edited.');
        }

        $this->editingId = $id;
        $this->form = $this->formFromRecord($record);
        $this->showForm = true;
    }

    public function saveRecord(): void
    {
        $this->authorizeSection();
        $this->authorizeReferenceType();

        match ($this->sectionKey) {
            'admin.users' => $this->saveUser(),
            'admin.branches' => $this->saveBranch(),
            'admin.reference' => $this->saveReference(),
            'admin.roles' => $this->saveRole(),
            'admin.settings' => $this->saveSetting(),
            'admin.workflows' => $this->saveWorkflow(),
        };

        $this->closeForm();
        session()->flash('status', 'Record saved successfully.');
    }

    public function deleteRecord(int $id): void
    {
        $this->authorizeSection();
        $this->authorizeReferenceType();

        match ($this->sectionKey) {
            'admin.users' => $this->deactivateUser($id),
            'admin.branches' => $this->deleteBranch($id),
            'admin.reference' => $this->deleteReference($id),
            'admin.roles' => $this->deleteRole($id),
            'admin.settings' => $this->deleteSetting($id),
            'admin.workflows' => $this->deleteWorkflow($id),
        };

        $this->closeForm();
    }

    public function closeForm(): void
    {
        $this->authorizeSection();
        $this->authorizeReferenceType();
        $this->showForm = false;
        $this->editingId = null;
        $this->form = [];
        $this->resetValidation();
    }

    public function addWorkflowStage(): void
    {
        $this->authorizeSection();
        abort_unless($this->sectionKey === 'admin.workflows' && $this->showForm, 403);

        $this->form['stages'][] = [
            'id' => null,
            'sequence' => count($this->form['stages']) + 1,
            'name' => '',
            'approver_role' => '',
            'permission' => '',
            'required_approvals' => 1,
            'is_final' => false,
        ];
    }

    public function removeWorkflowStage(int $index): void
    {
        $this->authorizeSection();
        abort_unless($this->sectionKey === 'admin.workflows' && $this->showForm, 403);

        unset($this->form['stages'][$index]);
        $this->form['stages'] = array_values($this->form['stages']);
    }

    /**
     * @return LengthAwarePaginator<int, array{id: int, label: string, detail: string, status: string, action: string, channel?: string}>
     */
    private function records(): LengthAwarePaginator
    {
        $query = match ($this->sectionKey) {
            'admin.users' => User::query()->with(['branch', 'roles'])->orderBy('name'),
            'admin.branches' => Branch::query()->withCount(['collectionPoints', 'users', 'payments', 'accounts'])->orderBy('name'),
            'admin.reference' => $this->referenceQuery(),
            'admin.workflows' => ApprovalWorkflow::query()->withCount(['stages', 'records', 'treatmentTypes'])->orderBy('name'),
            'admin.roles' => Role::query()->withCount(['permissions', 'users'])->orderBy('name'),
            'admin.settings' => SystemSetting::query()->orderBy('group')->orderBy('key'),
        };

        return $query->paginate(15)->through(fn (Model $record): array => $this->presentRecord($record));
    }

    private function referenceQuery(): Builder
    {
        return match ($this->referenceType) {
            'channels' => PaymentChannel::query()->orderBy('sort_order')->orderBy('name'),
            'banks' => Bank::query()->withCount(['bankBranches', 'payments', 'lodgements'])->orderBy('name'),
            'bank-branches' => BankBranch::query()->with('bank')->withCount('lodgements')->orderBy('name'),
            'collection-points' => CollectionPoint::query()->with('branch')->withCount(['payments', 'batches', 'shifts'])->orderBy('name'),
            'treatment-types' => AccountTreatmentType::query()->withCount(['requests', 'reasons'])->orderBy('name'),
            'treatment-reasons' => AccountTreatmentReason::query()->with(['type'])->withCount('requests')->orderBy('name'),
            default => throw new AccessDeniedHttpException,
        };
    }

    /**
     * @return array{id: int, label: string, detail: string, status: string, action: string, channel?: string}
     */
    private function presentRecord(Model $record): array
    {
        return match (true) {
            $record instanceof User => [
                'id' => $record->id,
                'label' => $record->name,
                'detail' => implode(' · ', array_filter([$record->email, $record->branch?->name, $record->getRoleNames()->implode(', ')])),
                'status' => $record->is_active ? 'Active' : 'Inactive',
                'action' => 'Deactivate',
            ],
            $record instanceof Branch => [
                'id' => $record->id,
                'label' => $record->code.' · '.$record->name,
                'detail' => $record->collection_points_count.' collection points · '.($record->users_count + $record->payments_count + $record->accounts_count).' linked records',
                'status' => $record->is_active ? 'Active' : 'Inactive',
                'action' => 'Delete or deactivate',
            ],
            $record instanceof ApprovalWorkflow => [
                'id' => $record->id,
                'label' => $record->name,
                'detail' => $record->code.' · '.$record->subject_type.' · '.$record->stages_count.' stages · '.($record->records_count + $record->treatment_types_count).' linked records',
                'status' => $record->is_active ? 'Active' : 'Inactive',
                'action' => 'Delete or deactivate',
            ],
            $record instanceof Role => [
                'id' => $record->id,
                'label' => $record->name,
                'detail' => $record->permissions_count.' permissions · '.$record->users_count.' users',
                'status' => $this->isProtectedRole($record)
                    ? 'Protected Administrator'
                    : (array_key_exists($record->name, RoleRegistry::roles()) ? 'Built-in role' : 'Custom role'),
                'action' => 'Delete if unused',
            ],
            $record instanceof SystemSetting => [
                'id' => $record->id,
                'label' => $record->label,
                'detail' => $record->group.' · '.$record->key.' · '.($record->is_public ? 'Public value' : 'Private value masked'),
                'status' => $record->type,
                'action' => 'Delete',
            ],
            default => $this->presentReference($record),
        };
    }

    /**
     * @return array{id: int, label: string, detail: string, status: string, action: string, channel?: string}
     */
    private function presentReference(Model $record): array
    {
        $label = match (true) {
            $record instanceof PaymentChannel => $record->name,
            $record instanceof Bank => $record->name,
            $record instanceof BankBranch => $record->name,
            $record instanceof CollectionPoint => $record->name,
            $record instanceof AccountTreatmentType => $record->name,
            $record instanceof AccountTreatmentReason => $record->name,
            default => class_basename($record),
        };

        $detail = match (true) {
            $record instanceof PaymentChannel => 'Payment channel · '.$record->code,
            $record instanceof Bank => 'Bank · '.$record->code.' · '.($record->bank_branches_count + $record->payments_count + $record->lodgements_count).' linked records',
            $record instanceof BankBranch => $record->bank?->name.' · '.$record->code.' · '.$record->lodgements_count.' linked lodgements',
            $record instanceof CollectionPoint => 'Collection point · '.$record->code.' · '.$record->branch?->name.' · '.($record->payments_count + $record->batches_count + $record->shifts_count).' linked records',
            $record instanceof AccountTreatmentType => 'Treatment type · '.$record->code.' · '.$record->requests_count.' requests · '.$record->reasons_count.' reasons',
            $record instanceof AccountTreatmentReason => 'Treatment reason · '.$record->type?->name.' · '.$record->code.' · '.$record->requests_count.' requests',
            default => '',
        };

        $data = [
            'id' => $record->getKey(),
            'label' => $label,
            'detail' => $detail,
            'status' => $record->is_active ? 'Active' : 'Inactive',
            'action' => 'Delete or deactivate',
        ];

        if ($record instanceof PaymentChannel) {
            $data['channel'] = $record->code;
        }

        return $data;
    }

    private function authorizeSection(): void
    {
        $section = self::SECTIONS[$this->sectionKey] ?? null;

        abort_unless($section, 404);

        $user = auth()->user();
        $hasPermission = $user->can($section['permission'])
            || ($this->sectionKey === 'admin.reference'
                && collect(self::REFERENCE_TYPES)->contains(fn (array $reference): bool => $user->can($reference['permission'])));

        if (! $hasPermission) {
            throw new AccessDeniedHttpException;
        }
    }

    private function authorizeReferenceType(): void
    {
        if ($this->sectionKey !== 'admin.reference') {
            return;
        }

        $reference = self::REFERENCE_TYPES[$this->referenceType] ?? null;
        abort_unless($reference, 404);

        if (! auth()->user()->can($reference['permission'])) {
            throw new AccessDeniedHttpException;
        }
    }

    private function findRecord(int $id): ?Model
    {
        return match ($this->sectionKey) {
            'admin.users' => User::query()->with('roles')->find($id),
            'admin.branches' => Branch::query()->find($id),
            'admin.reference' => $this->referenceModel()->find($id),
            'admin.workflows' => ApprovalWorkflow::query()->with(['stages'])->find($id),
            'admin.roles' => Role::query()->with('permissions')->find($id),
            'admin.settings' => SystemSetting::query()->find($id),
            default => null,
        };
    }

    private function referenceModel(): string
    {
        return match ($this->referenceType) {
            'channels' => PaymentChannel::class,
            'banks' => Bank::class,
            'bank-branches' => BankBranch::class,
            'collection-points' => CollectionPoint::class,
            'treatment-types' => AccountTreatmentType::class,
            'treatment-reasons' => AccountTreatmentReason::class,
            default => throw new AccessDeniedHttpException,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultForm(): array
    {
        return match ($this->sectionKey) {
            'admin.users' => ['name' => '', 'email' => '', 'employee_no' => '', 'phone' => '', 'job_title' => '', 'password' => '', 'branch_id' => '', 'collection_point_id' => '', 'branch_ids' => [], 'role_ids' => [], 'is_active' => true],
            'admin.branches' => ['code' => '', 'name' => '', 'address' => '', 'phone' => '', 'email' => '', 'is_active' => true],
            'admin.roles' => ['name' => '', 'permission_names' => []],
            'admin.settings' => ['key' => '', 'group' => 'general', 'label' => '', 'description' => '', 'type' => 'string', 'value' => '', 'is_public' => false, 'clear_value' => false],
            'admin.workflows' => ['code' => '', 'name' => '', 'subject_type' => AccountTreatmentRequest::class, 'description' => '', 'conditions_json' => '', 'is_active' => true, 'stages' => [$this->defaultWorkflowStage()]],
            'admin.reference' => $this->defaultReferenceForm(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultReferenceForm(): array
    {
        return match ($this->referenceType) {
            'channels' => ['code' => '', 'name' => '', 'description' => '', 'sort_order' => 0, 'required_fields' => '', 'settings_json' => '', 'is_active' => true, 'requires_receipt' => false, 'requires_external_reference' => false, 'requires_bank' => false, 'requires_bank_branch' => false, 'requires_terminal' => false, 'requires_cheque_details' => false, 'requires_settlement_date' => false],
            'banks' => ['code' => '', 'name' => '', 'short_name' => '', 'is_active' => true],
            'bank-branches' => ['bank_id' => '', 'code' => '', 'name' => '', 'sort_code' => '', 'address' => '', 'is_active' => true],
            'collection-points' => ['branch_id' => '', 'code' => '', 'name' => '', 'type' => 'cashier', 'is_active' => true],
            'treatment-types' => ['code' => '', 'name' => '', 'description' => '', 'requires_approval' => true, 'approval_workflow_id' => '', 'required_document_types' => '', 'sort_order' => 0, 'is_active' => true],
            'treatment-reasons' => ['account_treatment_type_id' => '', 'code' => '', 'name' => '', 'description' => '', 'is_active' => true],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function formFromRecord(Model $record): array
    {
        if ($record instanceof User) {
            return [
                'name' => $record->name,
                'email' => $record->email,
                'employee_no' => $record->employee_no,
                'phone' => $record->phone,
                'job_title' => $record->job_title,
                'password' => '',
                'branch_id' => $record->branch_id ?? '',
                'collection_point_id' => $record->collection_point_id ?? '',
                'branch_ids' => $record->branches()->pluck('branches.id')->all(),
                'role_ids' => $record->roles->pluck('id')->all(),
                'is_active' => $record->is_active,
            ];
        }

        if ($record instanceof Role) {
            return ['name' => $record->name, 'permission_names' => $record->permissions->pluck('name')->all()];
        }

        if ($record instanceof PaymentChannel) {
            return array_replace($this->defaultForm(), [
                'code' => $record->code,
                'name' => $record->name,
                'description' => $record->description,
                'sort_order' => $record->sort_order,
                'required_fields' => $record->required_fields === null ? '' : json_encode($record->required_fields),
                'settings_json' => $record->settings === null ? '' : json_encode($record->settings),
                'is_active' => $record->is_active,
                'requires_receipt' => $record->requires_receipt,
                'requires_external_reference' => $record->requires_external_reference,
                'requires_bank' => $record->requires_bank,
                'requires_bank_branch' => $record->requires_bank_branch,
                'requires_terminal' => $record->requires_terminal,
                'requires_cheque_details' => $record->requires_cheque_details,
                'requires_settlement_date' => $record->requires_settlement_date,
            ]);
        }

        if ($record instanceof AccountTreatmentType) {
            return array_replace($this->defaultForm(), [
                'code' => $record->code,
                'name' => $record->name,
                'description' => $record->description,
                'requires_approval' => $record->requires_approval,
                'approval_workflow_id' => $record->approval_workflow_id ?? '',
                'required_document_types' => implode(', ', $record->required_document_types ?? []),
                'sort_order' => $record->sort_order,
                'is_active' => $record->is_active,
            ]);
        }

        if ($record instanceof SystemSetting) {
            return ['key' => $record->key, 'group' => $record->group, 'label' => $record->label, 'description' => $record->description, 'type' => $record->type, 'value' => '', 'is_public' => $record->is_public, 'clear_value' => false];
        }

        if ($record instanceof ApprovalWorkflow) {
            return [
                'code' => $record->code,
                'name' => $record->name,
                'subject_type' => $record->subject_type,
                'description' => $record->description,
                'conditions_json' => $record->conditions === null ? '' : json_encode($record->conditions),
                'is_active' => $record->is_active,
                'stages' => $record->stages->map(fn (ApprovalWorkflowStage $stage): array => [
                    'id' => $stage->id,
                    'sequence' => $stage->sequence,
                    'name' => $stage->name,
                    'approver_role' => $stage->approver_role,
                    'permission' => $stage->permission,
                    'required_approvals' => $stage->required_approvals,
                    'is_final' => $stage->is_final,
                ])->all(),
            ];
        }

        return array_replace($this->defaultForm(), $record->only(array_keys($this->defaultForm())));
    }

    private function saveUser(): void
    {
        $record = $this->editingId ? User::query()->with('roles')->findOrFail($this->editingId) : new User;
        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($record->id)],
            'form.employee_no' => ['nullable', 'string', 'max:30', Rule::unique('users', 'employee_no')->ignore($record->id)],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.job_title' => ['nullable', 'string', 'max:100'],
            'form.password' => [$record->exists ? 'nullable' : 'required', 'string', 'min:12', 'max:255'],
            'form.branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'form.collection_point_id' => ['nullable', 'integer', Rule::exists('collection_points', 'id')->where('is_active', true)],
            'form.branch_ids' => ['array'],
            'form.branch_ids.*' => ['integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'form.role_ids' => ['required', 'array', 'min:1'],
            'form.role_ids.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'form.is_active' => ['required', 'boolean'],
        ])['form'];
        $validated['branch_id'] = $validated['branch_id'] ?: null;
        $validated['collection_point_id'] = $validated['collection_point_id'] ?: null;

        if ($validated['collection_point_id'] && (! $validated['branch_id'] || ! CollectionPoint::query()
            ->whereKey($validated['collection_point_id'])->where('branch_id', $validated['branch_id'])->exists())) {
            throw ValidationException::withMessages(['form.collection_point_id' => 'The collection point must belong to the selected branch.']);
        }

        $roles = Role::query()->where('guard_name', 'web')->whereKey($validated['role_ids'])->get();
        $keepsAdministrator = $roles->contains(fn (Role $role): bool => $role->name === RoleRegistry::ADMINISTRATOR);
        $deactivatesExistingAdministrator = $record->exists
            && $record->is_active
            && $record->hasRole(RoleRegistry::ADMINISTRATOR)
            && (! $validated['is_active'] || ! $keepsAdministrator);

        DB::transaction(function () use ($record, $validated, $roles, $deactivatesExistingAdministrator): void {
            $adminRole = Role::query()
                ->where('name', RoleRegistry::ADMINISTRATOR)
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->first();

            if ($deactivatesExistingAdministrator) {
                $this->ensureAnotherActiveAdministrator($record, $adminRole);
            }

            $before = $record->exists
                ? [
                    ...$record->only(['name', 'email', 'employee_no', 'branch_id', 'collection_point_id', 'is_active']),
                    'roles' => $record->getRoleNames()->all(),
                    'branch_ids' => $record->branches()->pluck('branches.id')->all(),
                ]
                : [];
            $attributes = collect($validated)->only(['name', 'email', 'employee_no', 'phone', 'job_title', 'branch_id', 'collection_point_id', 'is_active'])->all();
            if ($validated['password'] !== null && $validated['password'] !== '') {
                $attributes['password'] = $validated['password'];
            }
            $record->fill($attributes)->save();
            $record->branches()->sync($validated['branch_ids']);
            $record->syncRoles($roles);

            AuditLogger::log($record->wasRecentlyCreated ? 'created' : 'updated', $record, $before, [
                ...$attributes,
                'roles' => $roles->pluck('name')->all(),
                'branch_ids' => $validated['branch_ids'],
            ]);
        });
    }

    private function saveBranch(): void
    {
        $record = $this->editingId ? Branch::query()->findOrFail($this->editingId) : new Branch;
        $validated = $this->validate([
            'form.code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($record->id)],
            'form.name' => ['required', 'string', 'max:150'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.email' => ['nullable', 'email', 'max:255'],
            'form.is_active' => ['required', 'boolean'],
        ])['form'];
        $record->fill($validated)->save();
    }

    private function saveReference(): void
    {
        $model = $this->referenceModel();
        $record = $this->editingId ? $model::query()->findOrFail($this->editingId) : new $model;
        $rules = match ($this->referenceType) {
            'channels' => [
                'form.code' => ['required', Rule::in(array_column(PaymentChannelCode::cases(), 'value')), Rule::unique('payment_channels', 'code')->ignore($record->id)],
                'form.name' => ['required', 'string', 'max:80'],
                'form.description' => ['nullable', 'string', 'max:255'],
                'form.sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
                'form.required_fields' => ['nullable', 'string', 'max:2000'],
                'form.settings_json' => ['nullable', 'string', 'max:2000'],
                ...$this->booleanRules(['is_active', 'requires_receipt', 'requires_external_reference', 'requires_bank', 'requires_bank_branch', 'requires_terminal', 'requires_cheque_details', 'requires_settlement_date']),
            ],
            'banks' => [
                'form.code' => ['required', 'string', 'max:20', Rule::unique('banks', 'code')->ignore($record->id)],
                'form.name' => ['required', 'string', 'max:150'],
                'form.short_name' => ['nullable', 'string', 'max:50'],
                ...$this->booleanRules(['is_active']),
            ],
            'bank-branches' => [
                'form.bank_id' => ['required', 'integer', 'exists:banks,id'],
                'form.code' => ['required', 'string', 'max:30'],
                'form.name' => ['required', 'string', 'max:150'],
                'form.sort_code' => ['nullable', 'string', 'max:20'],
                'form.address' => ['nullable', 'string', 'max:255'],
                ...$this->booleanRules(['is_active']),
            ],
            'collection-points' => [
                'form.branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
                'form.code' => ['required', 'string', 'max:30', Rule::unique('collection_points', 'code')->ignore($record->id)],
                'form.name' => ['required', 'string', 'max:150'],
                'form.type' => ['required', 'string', 'max:30'],
                ...$this->booleanRules(['is_active']),
            ],
            'treatment-types' => [
                'form.code' => ['required', 'string', 'max:40', Rule::unique('account_treatment_types', 'code')->ignore($record->id)],
                'form.name' => ['required', 'string', 'max:120'],
                'form.description' => ['nullable', 'string', 'max:65535'],
                'form.requires_approval' => ['required', 'boolean'],
                'form.approval_workflow_id' => ['nullable', 'integer', Rule::exists('approval_workflows', 'id')->where('is_active', true)],
                'form.required_document_types' => ['nullable', 'string', 'max:2000'],
                'form.sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
                ...$this->booleanRules(['is_active']),
            ],
            'treatment-reasons' => [
                'form.account_treatment_type_id' => ['required', 'integer', Rule::exists('account_treatment_types', 'id')->where('is_active', true)],
                'form.code' => ['required', 'string', 'max:40'],
                'form.name' => ['required', 'string', 'max:150'],
                'form.description' => ['nullable', 'string', 'max:65535'],
                ...$this->booleanRules(['is_active']),
            ],
            default => throw new AccessDeniedHttpException,
        };

        $validated = $this->validate($rules)['form'];

        if ($record instanceof PaymentChannel) {
            $validated['required_fields'] = $this->decodeOptionalJson($validated['required_fields'], 'form.required_fields');
            $validated['settings'] = $this->decodeOptionalJson($validated['settings_json'], 'form.settings_json');
            unset($validated['settings_json']);
        }

        if ($record instanceof BankBranch && BankBranch::query()
            ->where('bank_id', $validated['bank_id'])
            ->where('code', $validated['code'])
            ->when($record->exists, fn ($query) => $query->where('id', '!=', $record->id))
            ->exists()) {
            throw ValidationException::withMessages(['form.code' => 'The code has already been taken for this bank.']);
        }

        if ($record instanceof AccountTreatmentReason && AccountTreatmentReason::query()
            ->where('account_treatment_type_id', $validated['account_treatment_type_id'])
            ->where('code', $validated['code'])
            ->when($record->exists, fn ($query) => $query->where('id', '!=', $record->id))
            ->exists()) {
            throw ValidationException::withMessages(['form.code' => 'The code has already been taken for this treatment type.']);
        }

        if ($record instanceof AccountTreatmentType) {
            $documents = array_values(array_filter(array_map('trim', explode(',', (string) $validated['required_document_types']))));
            $validated['required_document_types'] = $documents === [] ? null : $documents;
            $validated['approval_workflow_id'] = $validated['approval_workflow_id'] ?: null;
        }

        $record->fill($validated)->save();
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, array<int, string>>
     */
    private function booleanRules(array $keys): array
    {
        return collect($keys)->mapWithKeys(fn (string $key): array => ['form.'.$key => ['required', 'boolean']])->all();
    }

    private function saveRole(): void
    {
        $record = $this->editingId ? Role::query()->findOrFail($this->editingId) : new Role(['guard_name' => 'web']);
        abort_if($this->isProtectedRole($record), 403, 'The built-in Administrator role cannot be edited.');

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($record->id)],
            'form.permission_names' => ['array'],
            'form.permission_names.*' => ['required', 'string', Rule::in(PermissionRegistry::all())],
        ])['form'];
        if (array_key_exists($record->getOriginal('name') ?? '', RoleRegistry::roles()) && $validated['name'] !== $record->name) {
            throw ValidationException::withMessages(['form.name' => 'Built-in role names cannot be changed.']);
        }
        if ($record->exists && $validated['name'] !== $record->name && ApprovalWorkflowStage::query()
            ->where('approver_role', $record->name)
            ->exists()) {
            throw ValidationException::withMessages(['form.name' => 'A role used by an approval workflow cannot be renamed.']);
        }
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $validated['permission_names'])
            ->get();

        DB::transaction(function () use ($record, $validated, $permissions): void {
            $oldName = $record->name;
            $record->name = $validated['name'];
            $record->save();
            $record->syncPermissions($permissions);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            AuditLogger::log($record->wasRecentlyCreated ? 'created' : 'updated', $record, ['name' => $oldName], [
                'name' => $record->name,
                'permissions' => $permissions->pluck('name')->all(),
            ]);
        });
    }

    private function saveSetting(): void
    {
        $record = $this->editingId ? SystemSetting::query()->findOrFail($this->editingId) : new SystemSetting;
        $validated = $this->validate([
            'form.key' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('system_settings', 'key')->ignore($record->id)],
            'form.group' => ['required', 'string', 'max:50'],
            'form.label' => ['required', 'string', 'max:150'],
            'form.description' => ['nullable', 'string', 'max:255'],
            'form.type' => ['required', Rule::in(['string', 'boolean', 'integer', 'number', 'json'])],
            'form.value' => ['nullable', 'string', 'max:65535'],
            'form.is_public' => ['required', 'boolean'],
            'form.clear_value' => ['required', 'boolean'],
        ])['form'];

        $value = $validated['value'];
        if ($record->exists && ! $validated['clear_value'] && $value === '') {
            $value = $record->value;
        } elseif ($validated['clear_value']) {
            $value = null;
        } elseif ($value === null || $value === '') {
            throw ValidationException::withMessages(['form.value' => 'A setting value is required unless you choose to clear it.']);
        }

        if ($value !== null) {
            $valid = match ($validated['type']) {
                'boolean' => in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'], true),
                'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
                'number' => is_numeric($value),
                'json' => $this->isValidJson($value),
                default => true,
            };

            if (! $valid) {
                throw ValidationException::withMessages(['form.value' => 'The value must match the selected setting type.']);
            }
        }

        $record->fill([
            'key' => $validated['key'],
            'group' => $validated['group'],
            'label' => $validated['label'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'value' => $value,
            'is_public' => $validated['is_public'],
            'updated_by' => auth()->id(),
        ])->save();
        Setting::flush();
    }

    private function isValidJson(string $value): bool
    {
        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function saveWorkflow(): void
    {
        $record = $this->editingId ? ApprovalWorkflow::query()->with('stages')->findOrFail($this->editingId) : new ApprovalWorkflow;
        $validated = $this->validate([
            'form.code' => ['required', 'string', 'max:60', Rule::unique('approval_workflows', 'code')->ignore($record->id)],
            'form.name' => ['required', 'string', 'max:150'],
            'form.subject_type' => ['required', Rule::in([AccountTreatmentRequest::class, Payment::class])],
            'form.description' => ['nullable', 'string', 'max:65535'],
            'form.conditions_json' => ['nullable', 'string', 'max:5000'],
            'form.is_active' => ['required', 'boolean'],
            'form.stages' => ['required', 'array', 'min:1'],
            'form.stages.*.id' => ['nullable', 'integer'],
            'form.stages.*.sequence' => ['required', 'integer', 'min:1', 'max:65535'],
            'form.stages.*.name' => ['required', 'string', 'max:120'],
            'form.stages.*.approver_role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'form.stages.*.permission' => ['nullable', 'string', Rule::in(PermissionRegistry::all())],
            'form.stages.*.required_approvals' => ['required', 'integer', 'min:1', 'max:65535'],
            'form.stages.*.is_final' => ['required', 'boolean'],
        ])['form'];

        $sequences = array_column($validated['stages'], 'sequence');
        if (count($sequences) !== count(array_unique($sequences))) {
            throw ValidationException::withMessages(['form.stages' => 'Each stage must have a unique sequence number.']);
        }
        $validated['conditions'] = $this->decodeOptionalJson($validated['conditions_json'], 'form.conditions_json');
        unset($validated['conditions_json']);
        foreach ($validated['stages'] as &$stageData) {
            $stageData['permission'] = $stageData['permission'] ?: null;
        }
        unset($stageData);

        DB::transaction(function () use ($record, $validated): void {
            $record->fill(collect($validated)->except('stages')->all())->save();
            $existingStages = $record->stages()->get()->keyBy('id');
            $submittedIds = collect($validated['stages'])->pluck('id')->filter()->map(fn ($id): int => (int) $id);

            foreach ($existingStages as $stage) {
                if ($submittedIds->contains($stage->id)) {
                    continue;
                }

                if ($this->stageHasHistory($stage)) {
                    throw ValidationException::withMessages(['form.stages' => "Stage [{$stage->name}] has approval history and cannot be removed."]);
                }

                $stage->delete();
            }

            foreach ($validated['stages'] as $stageData) {
                $stage = isset($stageData['id']) ? $existingStages->get((int) $stageData['id']) : null;
                if (isset($stageData['id']) && ! $stage) {
                    throw ValidationException::withMessages(['form.stages' => 'A submitted approval stage does not belong to this workflow.']);
                }

                if ($stage && $this->stageHasHistory($stage)) {
                    $changed = collect($stage->only(['sequence', 'name', 'approver_role', 'permission', 'required_approvals', 'is_final']))
                        ->map(fn (mixed $value, string $key): mixed => $key === 'is_final' ? (bool) $value : ($key === 'permission' ? ($value ?: null) : (string) $value))
                        ->all()
                        !== collect($stageData)->only(['sequence', 'name', 'approver_role', 'permission', 'required_approvals', 'is_final'])
                            ->map(fn (mixed $value, string $key): mixed => $key === 'is_final' ? (bool) $value : ($key === 'permission' ? ($value ?: null) : (string) $value))
                            ->all();
                    if ($changed) {
                        throw ValidationException::withMessages(['form.stages' => "Stage [{$stage->name}] has approval history and cannot be changed."]);
                    }
                }

                $record->stages()->updateOrCreate(
                    ['id' => $stage?->id],
                    collect($stageData)->except('id')->all()
                );
            }
        });
    }

    private function stageHasHistory(ApprovalWorkflowStage $stage): bool
    {
        return $stage->records()->exists() || $stage->treatmentApprovals()->exists();
    }

    private function deactivateUser(int $id): void
    {
        $user = User::query()->with('roles')->findOrFail($id);
        abort_if(! $user->is_active, 403, 'This user is already inactive.');

        DB::transaction(function () use ($user): void {
            if ($user->hasRole(RoleRegistry::ADMINISTRATOR)) {
                $this->ensureAnotherActiveAdministrator($user);
            }

            $user->is_active = false;
            $user->save();
            AuditLogger::log('deactivated', $user, ['is_active' => true], ['is_active' => false]);
        });
    }

    private function ensureAnotherActiveAdministrator(User $user, ?Role $adminRole = null): void
    {
        $adminRole ??= Role::query()
            ->where('name', RoleRegistry::ADMINISTRATOR)
            ->where('guard_name', 'web')
            ->lockForUpdate()
            ->first();

        $remaining = User::query()
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->whereHas('roles', fn ($query) => $query->where('roles.id', $adminRole?->id))
            ->exists();

        if (! $remaining) {
            throw ValidationException::withMessages(['form.is_active' => 'The last active Administrator cannot be deactivated or have the Administrator role removed.']);
        }
    }

    private function deleteBranch(int $id): void
    {
        $branch = Branch::query()->findOrFail($id);
        $used = $branch->users()->exists()
            || User::query()->whereHas('branches', fn ($query) => $query->where('branches.id', $branch->id))->exists()
            || $branch->collectionPoints()->exists()
            || $branch->payments()->exists()
            || $branch->batches()->exists()
            || $branch->shifts()->exists()
            || $branch->accounts()->exists()
            || AccountTreatmentRequest::query()->where('branch_id', $branch->id)->exists();
        $this->deleteOrDeactivate($branch, $used);
    }

    private function deleteReference(int $id): void
    {
        $record = $this->referenceModel()::query()->findOrFail($id);

        $used = match (true) {
            $record instanceof PaymentChannel => $record->payments()->exists(),
            $record instanceof Bank => $record->bankBranches()->exists() || $record->payments()->exists() || $record->lodgements()->exists(),
            $record instanceof BankBranch => $record->lodgements()->exists() || Payment::query()->where('bank_branch_id', $record->id)->exists(),
            $record instanceof CollectionPoint => $record->payments()->exists() || $record->batches()->exists() || $record->shifts()->exists() || User::query()->where('collection_point_id', $record->id)->exists(),
            $record instanceof AccountTreatmentType => $record->requests()->exists(),
            $record instanceof AccountTreatmentReason => $record->requests()->exists(),
            default => false,
        };

        $this->deleteOrDeactivate($record, $used);
    }

    private function deleteOrDeactivate(Model $record, bool $used): void
    {
        if ($used) {
            if (! in_array('is_active', $record->getFillable(), true)) {
                throw ValidationException::withMessages(['form' => 'This record is in use and cannot be deactivated.']);
            }

            $record->is_active = false;
            $record->save();
            session()->flash('status', 'This record is referenced by existing data, so it was deactivated instead of deleted.');

            return;
        }

        $record->delete();
        session()->flash('status', 'Record deleted successfully.');
    }

    private function deleteRole(int $id): void
    {
        $role = Role::query()->findOrFail($id);
        abort_if(array_key_exists($role->name, RoleRegistry::roles()), 403, 'Built-in roles cannot be deleted.');

        if ($role->users()->exists()
            || $role->permissions()->exists()
            || ApprovalWorkflowStage::query()->where('approver_role', $role->name)->exists()) {
            throw ValidationException::withMessages(['form' => 'A role assigned to users, permissions, or approval stages cannot be deleted. Remove its assignments first.']);
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        session()->flash('status', 'Role deleted successfully.');
    }

    private function deleteSetting(int $id): void
    {
        SystemSetting::query()->findOrFail($id)->delete();
        Setting::flush();
        session()->flash('status', 'Setting deleted successfully.');
    }

    private function deleteWorkflow(int $id): void
    {
        $workflow = ApprovalWorkflow::query()->with('stages')->findOrFail($id);
        $used = $workflow->records()->exists()
            || $workflow->treatmentTypes()->exists()
            || AccountTreatmentRequest::query()->where('approval_workflow_id', $id)->exists();

        $this->deleteOrDeactivate($workflow, $used);
    }

    private function isProtectedRole(Role $role): bool
    {
        return $role->name === RoleRegistry::ADMINISTRATOR;
    }
};
