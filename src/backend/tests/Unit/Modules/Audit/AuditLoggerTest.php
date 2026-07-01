<?php

namespace Tests\Unit\Modules\Audit;

use App\Modules\Audit\Application\Services\AuditLogger;
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

    public function test_audit_logger_writes_row(): void
    {
        app(AuditLogger::class)->log(
            action: 'login',
            module: 'identity',
            entityType: 'user',
            entityId: 'user-123',
            actorUserId: null,
            beforePayload: null,
            afterPayload: ['email' => 'admin@ihrm.local'],
            result: 'success',
            occurredAt: now(),
            ipAddress: '127.0.0.1',
            userAgent: 'phpunit',
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login',
            'module' => 'identity',
            'entity_type' => 'user',
            'entity_id' => 'user-123',
            'result' => 'success',
        ]);
    }

    public function test_audit_logger_redacts_sensitive_nested_values(): void
    {
        $log = app(AuditLogger::class)->log(
            action: 'updated',
            module: 'identity',
            entityType: 'user',
            entityId: 'user-123',
            actorUserId: null,
            beforePayload: ['password' => 'old', 'profile' => ['api_key' => 'secret', 'name' => 'Admin']],
            afterPayload: ['access_token' => 'token', 'profile' => ['name' => 'Admin 2']],
            result: 'success',
            occurredAt: now(),
        );

        $this->assertSame('[REDACTED]', $log->before_payload['password']);
        $this->assertSame('[REDACTED]', $log->before_payload['profile']['api_key']);
        $this->assertSame('Admin', $log->before_payload['profile']['name']);
        $this->assertSame('[REDACTED]', $log->after_payload['access_token']);
    }
}
