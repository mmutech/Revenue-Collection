<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PermissionRegistry;
use App\Support\RoleRegistry;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

class AdministratorAuthorizationTest extends TestCase
{
    public function test_administrator_can_use_every_registered_permission_but_not_unregistered_abilities(): void
    {
        $administrator = Mockery::mock(User::class);
        $administrator->shouldReceive('hasRole')
            ->with(RoleRegistry::ADMINISTRATOR)
            ->andReturnTrue();
        $administrator->shouldReceive('checkPermissionTo')->andReturnFalse();

        foreach (PermissionRegistry::all() as $permission) {
            $this->assertTrue(
                Gate::forUser($administrator)->allows($permission),
                "Administrator should be authorized for [{$permission}]."
            );
        }

        $this->assertFalse(Gate::forUser($administrator)->allows('unregistered.ability'));
    }

    public function test_non_administrator_without_permissions_is_not_granted_registered_permissions(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasRole')
            ->with(RoleRegistry::ADMINISTRATOR)
            ->andReturnFalse();
        $user->shouldReceive('checkPermissionTo')->andReturnFalse();

        $this->assertFalse(Gate::forUser($user)->allows('payments.reverse'));
    }
}
