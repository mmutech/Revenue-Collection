<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CollectionPoint;
use App\Models\User;
use App\Support\PermissionRegistry;
use App\Support\RoleRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionRegistry::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (RoleRegistry::roles() as $name => $definition) {
            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($definition['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $hq = Branch::query()->where('code', 'HQ')->firstOrFail();
        $jos = Branch::query()->where('code', 'JO')->firstOrFail();
        $ba = Branch::query()->where('code', 'BA')->firstOrFail();

        $hqCash = CollectionPoint::query()->where('code', 'HQ-CASH-01')->first();
        $hqPos = CollectionPoint::query()->where('code', 'HQ-POS-01')->first();
        $josCash = CollectionPoint::query()->where('code', 'JO-CASH-01')->first();

        $users = [
            [
                'name' => 'Amaka Obi',
                'email' => 'admin@example.com',
                'employee_no' => 'EMP-0001',
                'job_title' => 'System Administrator',
                'role' => RoleRegistry::ADMINISTRATOR,
                'branch_id' => $hq->id,
                'collection_point_id' => null,
            ],
            [
                'name' => 'Chinedu Balogun',
                'email' => 'manager@example.com',
                'employee_no' => 'EMP-0002',
                'job_title' => 'Revenue Manager',
                'role' => RoleRegistry::MANAGER,
                'branch_id' => $hq->id,
                'collection_point_id' => null,
            ],
            [
                'name' => 'Fatima Yusuf',
                'email' => 'supervisor@example.com',
                'employee_no' => 'EMP-0003',
                'job_title' => 'Collections Supervisor',
                'role' => RoleRegistry::SUPERVISOR,
                'branch_id' => $hq->id,
                'collection_point_id' => null,
            ],
            [
                'name' => 'Tunde Adeyemi',
                'email' => 'cashier@example.com',
                'employee_no' => 'EMP-0101',
                'job_title' => 'Revenue Cashier',
                'role' => RoleRegistry::CASHIER,
                'branch_id' => $hq->id,
                'collection_point_id' => $hqCash?->id,
            ],
            [
                'name' => 'Ngozi Eze',
                'email' => 'cashier2@example.com',
                'employee_no' => 'EMP-0102',
                'job_title' => 'Revenue Cashier',
                'role' => RoleRegistry::CASHIER,
                'branch_id' => $jos->id,
                'collection_point_id' => $josCash?->id,
            ],
            [
                'name' => 'Bola Martins',
                'email' => 'cashier3@example.com',
                'employee_no' => 'EMP-0103',
                'job_title' => 'Revenue Cashier',
                'role' => RoleRegistry::CASHIER,
                'branch_id' => $hq->id,
                'collection_point_id' => $hqPos?->id,
            ],
            [
                'name' => 'Ibrahim Sule',
                'email' => 'clearing@example.com',
                'employee_no' => 'EMP-0201',
                'job_title' => 'Clearing Officer',
                'role' => RoleRegistry::CLEARING_CASHIER,
                'branch_id' => $hq->id,
                'collection_point_id' => null,
            ],
            [
                'name' => 'Grace Nwosu',
                'email' => 'bank@example.com',
                'employee_no' => 'EMP-0301',
                'job_title' => 'Bank Liaison Officer',
                'role' => RoleRegistry::BANK_OFFICER,
                'branch_id' => $hq->id,
                'collection_point_id' => null,
            ],
            [
                'name' => 'Yusuf Danjuma',
                'email' => 'treasury@example.com',
                'employee_no' => 'EMP-0401',
                'job_title' => 'Treasury Officer',
                'role' => RoleRegistry::TREASURY_OFFICER,
                'branch_id' => $hq->id,
                'collection_point_id' => null,
            ],
            [
                'name' => 'Halima Bello',
                'email' => 'auditor@example.com',
                'employee_no' => 'EMP-0501',
                'job_title' => 'Internal Auditor',
                'role' => RoleRegistry::AUDITOR,
                'branch_id' => $hq->id,
                'collection_point_id' => null,
            ],
            [
                'name' => 'Emeka Okafor',
                'email' => 'ph.cashier@example.com',
                'employee_no' => 'EMP-0104',
                'job_title' => 'Revenue Cashier',
                'role' => RoleRegistry::CASHIER,
                'branch_id' => $ba->id,
                'collection_point_id' => null,
            ],
        ];

        foreach ($users as $definition) {
            $user = User::query()->firstOrNew(['email' => $definition['email']]);
            $needsRandomPassword = ! $user->exists || Hash::check('password', $user->password);

            $user->fill([
                'name' => $definition['name'],
                'employee_no' => $definition['employee_no'],
                'job_title' => $definition['job_title'],
                'branch_id' => $definition['branch_id'],
                'collection_point_id' => $definition['collection_point_id'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            if ($needsRandomPassword) {
                $user->password = Hash::make('password');
            }

            $user->save();

            $user->syncRoles([$definition['role']]);
            $user->branches()->syncWithoutDetaching([$definition['branch_id']]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
