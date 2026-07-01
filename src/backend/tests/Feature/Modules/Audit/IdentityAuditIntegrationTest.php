<?php

namespace Tests\Feature\Modules\Audit;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\PermissionModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use App\Modules\Identity\Infrastructure\Seeders\AdminUserSeeder;
use App\Modules\Identity\Infrastructure\Seeders\PermissionSeeder;
use App\Modules\Identity\Infrastructure\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentityAuditIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function seedIdentity(): UserModel
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(AdminUserSeeder::class);
        return UserModel::where('email', 'admin@ihrm.local')->firstOrFail();
    }

    public function test_successful_login_creates_audit_log(): void
    {
        $admin = $this->seedIdentity();

        $this->postJson('/api/v1/auth/login', ['email' => 'admin@ihrm.local', 'password' => 'password'])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login',
            'module' => 'identity',
            'entity_type' => 'user',
            'entity_id' => $admin->id,
            'result' => 'success',
        ]);
    }

    public function test_failed_login_creates_audit_log(): void
    {
        $this->seedIdentity();

        $this->postJson('/api/v1/auth/login', ['email' => 'admin@ihrm.local', 'password' => 'wrong'])->assertStatus(500);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_failed',
            'module' => 'identity',
            'entity_type' => 'user',
            'result' => 'failure',
        ]);
    }

    public function test_role_permission_grant_creates_audit_log(): void
    {
        $admin = $this->seedIdentity();
        $role = RoleModel::create(['code' => 'AUDIT_TEST', 'name' => 'Audit Test', 'active' => true]);
        PermissionModel::firstOrCreate(['code' => 'identity.user.list'], ['module' => 'identity', 'action' => 'user.list', 'description' => 'List users', 'active' => true]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/roles/{$role->id}/permissions", ['permission_code' => 'identity.user.list'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permission_granted',
            'module' => 'identity',
            'entity_type' => 'role',
            'entity_id' => $role->id,
            'result' => 'success',
        ]);
    }
}
