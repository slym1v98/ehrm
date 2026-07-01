<?php

namespace Tests\Feature\Modules\Identity;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use App\Modules\Identity\Infrastructure\Seeders\AdminUserSeeder;
use App\Modules\Identity\Infrastructure\Seeders\PermissionSeeder;
use App\Modules\Identity\Infrastructure\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentityHttpTest extends TestCase
{
    use RefreshDatabase;

    private function seedIdentity(): UserModel
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(AdminUserSeeder::class);
        return UserModel::where('email', 'admin@ihrm.local')->firstOrFail();
    }

    public function test_login_returns_token_and_user(): void
    {
        $this->seedIdentity();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@ihrm.local',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'user' => ['id', 'email', 'roles']]]);
    }

    public function test_me_and_permissions_work_for_admin(): void
    {
        $admin = $this->seedIdentity();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@ihrm.local');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/permissions')
            ->assertOk()
            ->assertJsonStructure(['data' => [['code', 'module', 'action', 'active']]]);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = UserModel::create([
            'name' => 'No Permission',
            'email' => 'no-permission@ihrm.local',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertStatus(403)
            ->assertJsonStructure(['error' => ['code', 'message', 'trace_id']]);
    }
}
