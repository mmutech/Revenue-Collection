<?php

use App\Models\AccountTreatmentType;
use App\Models\ApprovalWorkflow;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\CollectionPoint;
use App\Models\PaymentChannel;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

new #[Layout('components.layouts.app')]
#[Title('Administration')] class extends Component
{
    #[Locked]
    public string $sectionKey = '';

    /**
     * @var array<string, array{title: string, permission: string, description: string}>
     */
    private const SECTIONS = [
        'admin.users' => [
            'title' => 'Users',
            'permission' => 'admin.users',
            'description' => 'User accounts and their assigned branches.',
        ],
        'admin.branches' => [
            'title' => 'Branches',
            'permission' => 'admin.branches',
            'description' => 'Configured branches and collection points.',
        ],
        'admin.reference' => [
            'title' => 'Reference data',
            'permission' => 'admin.channels',
            'description' => 'Payment channels, banks, and collection points.',
        ],
        'admin.workflows' => [
            'title' => 'Approval workflows',
            'permission' => 'admin.workflows',
            'description' => 'Configured approval workflows and stages.',
        ],
        'admin.roles' => [
            'title' => 'Roles and permissions',
            'permission' => 'admin.roles',
            'description' => 'System roles and permission assignments.',
        ],
        'admin.settings' => [
            'title' => 'System settings',
            'permission' => 'admin.settings',
            'description' => 'Configured settings. Values are intentionally not displayed here.',
        ],
    ];

    public function mount(): void
    {
        $this->sectionKey = (string) request()->route()->getName();
        $section = self::SECTIONS[$this->sectionKey] ?? null;

        abort_unless($section, 404);

        $user = auth()->user();
        $hasPermission = $user->can($section['permission'])
            || ($this->sectionKey === 'admin.reference' && $user->can('admin.banks'));

        if (! $hasPermission) {
            throw new AccessDeniedHttpException;
        }
    }

    public function render(): View
    {
        $section = self::SECTIONS[$this->sectionKey];

        return view('pages.admin.⚡index.index', [
            'section' => $section,
            'records' => $this->records($this->sectionKey),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, detail: string, status: string}>
     */
    private function records(string $routeName): Collection
    {
        return match ($routeName) {
            'admin.users' => User::query()
                ->with(['branch', 'roles'])
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => [
                    'label' => $user->name,
                    'detail' => implode(' · ', array_filter([$user->email, $user->branch?->name, $user->getRoleNames()->implode(', ')])),
                    'status' => $user->is_active ? 'Active' : 'Inactive',
                ]),
            'admin.branches' => Branch::query()
                ->withCount('collectionPoints')
                ->orderBy('name')
                ->get()
                ->map(fn (Branch $branch) => [
                    'label' => $branch->code.' · '.$branch->name,
                    'detail' => $branch->collection_points_count.' collection points',
                    'status' => $branch->is_active ? 'Active' : 'Inactive',
                ]),
            'admin.reference' => $this->referenceRecords(),
            'admin.workflows' => ApprovalWorkflow::query()
                ->withCount('stages')
                ->orderBy('name')
                ->get()
                ->map(fn (ApprovalWorkflow $workflow) => [
                    'label' => $workflow->name,
                    'detail' => $workflow->code.' · '.$workflow->subject_type.' · '.$workflow->stages_count.' stages',
                    'status' => $workflow->is_active ? 'Active' : 'Inactive',
                ]),
            'admin.roles' => Role::query()
                ->withCount('permissions')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'label' => $role->name,
                    'detail' => $role->permissions_count.' permissions',
                    'status' => 'Configured',
                ]),
            'admin.settings' => SystemSetting::query()
                ->orderBy('group')
                ->orderBy('key')
                ->get()
                ->map(fn (SystemSetting $setting) => [
                    'label' => $setting->label,
                    'detail' => $setting->group.' · '.$setting->key,
                    'status' => $setting->type,
                ]),
            default => collect(),
        };
    }

    /**
     * @return Collection<int, array{label: string, detail: string, status: string}>
     */
    private function referenceRecords(): Collection
    {
        $user = auth()->user();
        $records = collect();

        if ($user->can('admin.channels')) {
            $records = $records->concat(PaymentChannel::query()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (PaymentChannel $channel) => [
                    'label' => $channel->name,
                    'detail' => 'Payment channel · '.$channel->code,
                    'status' => $channel->is_active ? 'Active' : 'Inactive',
                ]));
        }

        if ($user->can('admin.banks')) {
            $records = $records->concat(Bank::query()->orderBy('name')->get()->map(fn (Bank $bank) => [
                'label' => $bank->name,
                'detail' => 'Bank · '.$bank->code,
                'status' => $bank->is_active ? 'Active' : 'Inactive',
            ]));
        }

        if ($user->can('admin.branches')) {
            $records = $records->concat(CollectionPoint::query()->with('branch')->orderBy('name')->get()->map(fn (CollectionPoint $point) => [
                'label' => $point->name,
                'detail' => 'Collection point · '.$point->code.' · '.$point->branch?->name,
                'status' => $point->is_active ? 'Active' : 'Inactive',
            ]));
        }

        if ($user->can('treatment.manage')) {
            $records = $records->concat(AccountTreatmentType::query()->orderBy('name')->get()->map(fn (AccountTreatmentType $type) => [
                'label' => $type->name,
                'detail' => 'Treatment type · '.$type->code,
                'status' => $type->is_active ? 'Active' : 'Inactive',
            ]));
        }

        return $records->values();
    }
};
