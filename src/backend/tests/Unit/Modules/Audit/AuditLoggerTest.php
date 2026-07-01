<?php

namespace Tests\Unit\Modules\Audit;

use App\Modules\Audit\Infrastructure\Persistence\Eloquent\AuditLogModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_model_persists_login_failure(): void
    {
        $auditLog = AuditLogModel::create([
            'actor_user_id' => null,
            'action' => 'login_failed',
            'module' => 'identity',
            'entity_type' => 'user',
            'entity_id' => null,
            'before_payload' => null,
            'after_payload' => ['email' => 'missing@ihrm.local'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'result' => 'failure',
            'occurred_at' => now(),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
            'action' => 'login_failed',
            'module' => 'identity',
            'result' => 'failure',
        ]);
    }
}
