<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUsersAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_users_manage_permission_can_access_admin_users_index(): void
    {
        $permission = Permission::create(['name' => 'users.manage']);
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/admin/users')->assertOk();
    }
}
