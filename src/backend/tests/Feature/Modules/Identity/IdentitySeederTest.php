<?php

namespace Tests\Feature\Modules\Identity;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\PermissionModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserRoleModel;
use App\Modules\Identity\Infrastructure\Seeders\AdminUserSeeder;
use App\Modules\Identity\Infrastructure\Seeders\PermissionSeeder;
use App\Modules\Identity\Infrastructure\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IdentitySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_populate_permissions_roles_and_admin(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertGreaterThanOrEqual(18, PermissionModel::count());
        $this->assertTrue(RoleModel::where('code', 'SUPER_ADMIN')->exists());
        $this->assertTrue(RoleModel::where('code', 'HR_MANAGER')->exists());
        $this->assertTrue(RoleModel::where('code', 'EMPLOYEE')->exists());

        $superAdmin = RoleModel::where('code', 'SUPER_ADMIN')->first();
        $this->assertSame(PermissionModel::count(), $superAdmin->rolePermissions()->count());

        $admin = UserModel::where('email', 'admin@ihrm.local')->first();
        $this->assertNotNull($admin);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue(UserRoleModel::where('user_id', $admin->id)->where('role_id', $superAdmin->id)->exists());
    }
}
