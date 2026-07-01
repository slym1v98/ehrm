<?php

namespace Tests\Feature\Modules\Audit;

use App\Modules\Audit\Infrastructure\Persistence\Eloquent\AuditLogModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use App\Modules\Identity\Infrastructure\Seeders\AdminUserSeeder;
use App\Modules\Identity\Infrastructure\Seeders\PermissionSeeder;
use App\Modules\Identity\Infrastructure\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedAdmin(): UserModel
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(AdminUserSeeder::class);
        return UserModel::where('email', 'admin@ihrm.local')->firstOrFail();
    }

    public function test_admin_can_list_audit_logs(): void
    {
        $admin = $this->seedAdmin();
        AuditLogModel::create(['actor_user_id' => $admin->id, 'action' => 'login', 'module' => 'identity', 'entity_type' => 'user', 'entity_id' => $admin->id, 'result' => 'success', 'occurred_at' => now()]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/audit-logs')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'actor_user_id', 'action', 'module', 'entity_type', 'result', 'occurred_at']], 'meta' => ['total']]);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = UserModel::create(['name' => 'No Permission', 'email' => 'no-permission@ihrm.local', 'password' => bcrypt('password'), 'status' => 'active']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/audit-logs')
            ->assertStatus(403);
    }

    public function test_filters_by_action_module_entity_result_and_date(): void
    {
        $admin = $this->seedAdmin();
        AuditLogModel::create(['actor_user_id' => $admin->id, 'action' => 'login', 'module' => 'identity', 'entity_type' => 'user', 'entity_id' => $admin->id, 'result' => 'success', 'occurred_at' => '2026-07-01 10:00:00']);
        AuditLogModel::create(['actor_user_id' => null, 'action' => 'login_failed', 'module' => 'identity', 'entity_type' => 'user', 'entity_id' => null, 'result' => 'failure', 'occurred_at' => '2026-07-02 10:00:00']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/audit-logs?action=login&module=identity&entity_type=user&result=success&date_from=2026-07-01&date_to=2026-07-01')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'login');
    }
}
