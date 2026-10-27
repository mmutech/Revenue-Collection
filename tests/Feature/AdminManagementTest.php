<?php

namespace Tests\Feature;

use App\Enums\PaymentChannelCode;
use App\Models\AccountTreatmentRequest;
use App\Models\AccountTreatmentType;
use App\Models\ApprovalRecord;
use App\Models\ApprovalWorkflow;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CollectionPoint;
use App\Models\PaymentChannel;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\RoleRegistry;
use App\Support\Setting;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::flush();
    }

    protected function tearDown(): void
    {
        Setting::flush();
        parent::tearDown();
    }

    public function test_administrator_can_create_a_bank_from_reference_data(): void
    {
        $administrator = $this->createAdministrator();

        Livewire::actingAs($administrator)
            ->test('pages::admin.index', ['section' => 'admin.reference'])
            ->set('referenceType', 'banks')
            ->call('createRecord')
            ->set('form.code', 'NORTH')
            ->set('form.name', 'North Bank')
            ->set('form.short_name', 'NB')
            ->set('form.is_active', true)
            ->call('saveRecord')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('banks', [
            'code' => 'NORTH',
            'name' => 'North Bank',
            'short_name' => 'NB',
            'is_active' => true,
        ]);
    }

    public function test_payment_channel_uses_registered_channel_codes_and_the_channel_badge(): void
    {
        $administrator = $this->createAdministrator();

        Livewire::actingAs($administrator)
            ->test('pages::admin.index', ['section' => 'admin.reference'])
            ->set('referenceType', 'channels')
            ->call('createRecord')
            ->set('form.code', PaymentChannelCode::Cash->value)
            ->set('form.name', 'Cash')
            ->set('form.sort_order', 1)
            ->set('form.required_fields', '')
            ->set('form.settings_json', '')
            ->set('form.is_active', true)
            ->set('form.requires_receipt', true)
            ->set('form.requires_external_reference', false)
            ->set('form.requires_bank', false)
            ->set('form.requires_bank_branch', false)
            ->set('form.requires_terminal', false)
            ->set('form.requires_cheque_details', false)
            ->set('form.requires_settlement_date', false)
            ->call('saveRecord')
            ->assertHasNoErrors()
            ->assertSee('Cash');

        $this->assertDatabaseHas('payment_channels', [
            'code' => PaymentChannelCode::Cash->value,
            'name' => 'Cash',
        ]);

        $channel = PaymentChannel::query()->where('code', PaymentChannelCode::Cash->value)->firstOrFail();
        $this->assertSame(PaymentChannelCode::Cash, $channel->channelCode());
    }

    public function test_referenced_branch_is_deactivated_instead_of_deleted(): void
    {
        $administrator = $this->createAdministrator();
        $branch = Branch::query()->create(['code' => 'BR-1', 'name' => 'Central Branch']);
        CollectionPoint::query()->create([
            'branch_id' => $branch->id,
            'code' => 'CP-1',
            'name' => 'Main Counter',
        ]);

        Livewire::actingAs($administrator)
            ->test('pages::admin.index', ['section' => 'admin.branches'])
            ->call('deleteRecord', $branch->id)
            ->assertHasNoErrors();

        $this->assertModelExists($branch->refresh());
        $this->assertFalse($branch->is_active);
    }

    public function test_administrator_can_create_a_user_with_branch_and_role_assignments(): void
    {
        $administrator = $this->createAdministrator();
        $managerRole = Role::findOrCreate(RoleRegistry::MANAGER, 'web');
        $branch = Branch::query()->create(['code' => 'BR-2', 'name' => 'North Branch']);

        Livewire::actingAs($administrator)
            ->test('pages::admin.index', ['section' => 'admin.users'])
            ->call('createRecord')
            ->set('form.name', 'Jordan Lee')
            ->set('form.email', 'jordan@example.test')
            ->set('form.employee_no', 'EMP-0099')
            ->set('form.phone', '555-0100')
            ->set('form.job_title', 'Revenue Manager')
            ->set('form.password', 'long-password-123')
            ->set('form.branch_id', $branch->id)
            ->set('form.collection_point_id', '')
            ->set('form.branch_ids', [$branch->id])
            ->set('form.role_ids', [$managerRole->id])
            ->set('form.is_active', true)
            ->call('saveRecord')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'jordan@example.test')->firstOrFail();
        $this->assertSame($branch->id, $user->branch_id);
        $this->assertSame([$branch->id], $user->branches->modelKeys());
        $this->assertTrue(Hash::check('long-password-123', $user->password));
        $this->assertTrue($user->hasRole(RoleRegistry::MANAGER));
    }

    public function test_administrator_can_create_a_custom_role_with_registered_permissions(): void
    {
        $administrator = $this->createAdministrator();
        Permission::findOrCreate('payments.view', 'web');

        Livewire::actingAs($administrator)
            ->test('pages::admin.index', ['section' => 'admin.roles'])
            ->call('createRecord')
            ->set('form.name', 'Payment Reviewer')
            ->set('form.permission_names', ['payments.view'])
            ->call('saveRecord')
            ->assertHasNoErrors();

        $role = Role::findByName('Payment Reviewer', 'web');
        $this->assertTrue($role->hasPermissionTo('payments.view'));
    }

    public function test_treatment_manager_can_access_only_their_reference_data_area(): void
    {
        Permission::findOrCreate('treatment.manage', 'web');
        $role = Role::findOrCreate('Treatment Configuration', 'web');
        $role->givePermissionTo('treatment.manage');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        Livewire::actingAs($user)
            ->test('pages::admin.index', ['section' => 'admin.reference'])
            ->assertSee('Treatment types')
            ->assertSee('Treatment reasons')
            ->assertDontSee('Payment channels');
    }

    public function test_administrator_can_create_a_treatment_type_without_a_workflow(): void
    {
        $administrator = $this->createAdministrator();

        Livewire::actingAs($administrator)
            ->test('pages::admin.index', ['section' => 'admin.reference'])
            ->set('referenceType', 'treatment-types')
            ->call('createRecord')
            ->set('form.code', 'WAIVER')
            ->set('form.name', 'Penalty waiver')
            ->set('form.description', '')
            ->set('form.requires_approval', false)
            ->set('form.approval_workflow_id', '')
            ->set('form.required_document_types', '')
            ->set('form.sort_order', 1)
            ->set('form.is_active', true)
            ->call('saveRecord')
            ->assertHasNoErrors();

        $treatmentType = AccountTreatmentType::query()->where('code', 'WAIVER')->firstOrFail();
        $this->assertNull($treatmentType->approval_workflow_id);
        $this->assertNull($treatmentType->required_document_types);
    }

    public function test_last_active_administrator_cannot_be_deactivated(): void
    {
        $administrator = $this->createAdministrator();

        Livewire::actingAs($administrator)
            ->test('pages::admin.index', ['section' => 'admin.users'])
            ->call('deleteRecord', $administrator->id)
            ->assertHasErrors(['form.is_active']);

        $this->assertTrue($administrator->refresh()->is_active);
    }

    public function test_private_setting_values_are_masked_from_audit_logs_and_setting_cache_is_flushed(): void
    {
        $administrator = $this->createAdministrator();
        Setting::get('finance.test_secret', 'missing');

        $component = Livewire::actingAs($administrator)
            ->test('pages::admin.index', ['section' => 'admin.settings'])
            ->call('createRecord')
            ->set('form.key', 'finance.test_secret')
            ->set('form.group', 'finance')
            ->set('form.label', 'Test secret')
            ->set('form.description', 'Private finance setting')
            ->set('form.type', 'string')
            ->set('form.value', 'initial-private-value')
            ->set('form.is_public', false)
            ->set('form.clear_value', false)
            ->call('saveRecord')
            ->assertHasNoErrors();

        $setting = SystemSetting::query()->where('key', 'finance.test_secret')->firstOrFail();
        $this->assertSame('initial-private-value', Setting::get('finance.test_secret'));

        $component->call('editRecord', $setting->id)
            ->set('form.value', 'updated-private-value')
            ->call('saveRecord')
            ->assertHasNoErrors();

        $this->assertSame('updated-private-value', Setting::get('finance.test_secret'));

        $logs = AuditLog::query()
            ->where('auditable_type', $setting->getMorphClass())
            ->where('auditable_id', $setting->id)
            ->get();

        $this->assertCount(2, $logs);
        foreach ($logs as $log) {
            $this->assertArrayNotHasKey('value', $log->new_values ?? []);
            $this->assertArrayNotHasKey('value', $log->old_values ?? []);
        }
    }

    public function test_workflow_stage_with_approval_history_cannot_be_changed(): void
    {
        $administrator = $this->createAdministrator();
        Role::findOrCreate(RoleRegistry::MANAGER, 'web');
        $workflow = ApprovalWorkflow::query()->create([
            'code' => 'HISTORY',
            'name' => 'Historical workflow',
            'subject_type' => AccountTreatmentRequest::class,
            'is_active' => true,
        ]);
        $stage = $workflow->stages()->create([
            'sequence' => 1,
            'name' => 'First review',
            'approver_role' => RoleRegistry::MANAGER,
            'required_approvals' => 1,
            'is_final' => true,
        ]);
        ApprovalRecord::query()->create([
            'approval_workflow_id' => $workflow->id,
            'subject_type' => AccountTreatmentRequest::class,
            'subject_id' => 1,
            'approval_workflow_stage_id' => $stage->id,
            'stage_sequence' => 1,
            'decision' => 'approved',
        ]);

        Livewire::actingAs($administrator)
            ->test('pages::admin.index', ['section' => 'admin.workflows'])
            ->call('editRecord', $workflow->id)
            ->set('form.stages.0.name', 'Renamed historical stage')
            ->call('saveRecord')
            ->assertHasErrors(['form.stages']);

        $this->assertSame('First review', $stage->refresh()->name);
    }

    public function test_reference_seeding_preserves_workflow_stages_with_approval_history(): void
    {
        $workflow = ApprovalWorkflow::query()->create([
            'code' => 'treatment-standard',
            'name' => 'Standard account treatment approval',
            'subject_type' => AccountTreatmentRequest::class,
            'is_active' => true,
        ]);
        $stage = $workflow->stages()->create([
            'sequence' => 1,
            'name' => 'Historical supervisor stage',
            'approver_role' => RoleRegistry::SUPERVISOR,
            'permission' => 'treatment.review',
            'required_approvals' => 1,
            'is_final' => false,
        ]);
        ApprovalRecord::query()->create([
            'approval_workflow_id' => $workflow->id,
            'subject_type' => AccountTreatmentRequest::class,
            'subject_id' => 1,
            'approval_workflow_stage_id' => $stage->id,
            'stage_sequence' => 1,
            'decision' => 'approved',
        ]);

        app(ReferenceDataSeeder::class)->run();

        $this->assertSame('Historical supervisor stage', $stage->refresh()->name);
        $this->assertDatabaseHas('approval_workflow_stages', [
            'approval_workflow_id' => $workflow->id,
            'sequence' => 2,
            'name' => 'Manager approval',
        ]);
    }

    public function test_user_without_admin_permission_cannot_open_admin_pages(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('admin.reference'))
            ->assertForbidden();
    }

    private function createAdministrator(): User
    {
        $role = Role::findOrCreate(RoleRegistry::ADMINISTRATOR, 'web');
        $administrator = User::factory()->create();
        $administrator->assignRole($role);

        return $administrator;
    }
}
