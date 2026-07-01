<?php

namespace Tests\Unit\Modules\Audit;

use App\Modules\Audit\Infrastructure\Listeners\AuditEventListener;
use App\Modules\Audit\Infrastructure\Persistence\Eloquent\AuditLogModel;
use App\Modules\Identity\Domain\Aggregates\Role\PermissionCode;
use App\Modules\Identity\Domain\Aggregates\Role\RoleCode;
use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use App\Modules\Identity\Domain\Aggregates\User\Email;
use App\Modules\Identity\Domain\Aggregates\User\UserId;
use App\Modules\Identity\Domain\Events\RoleCreated;
use App\Modules\Identity\Domain\Events\RolePermissionGranted;
use App\Modules\Identity\Domain\Events\UserCreated;
use App\Modules\Identity\Domain\Events\UserLoginFailed;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditEventListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_maps_user_created_event(): void
    {
        $userId = UserId::generate();

        app(AuditEventListener::class)->handle(new UserCreated(
            userId: $userId,
            email: Email::fromString('admin@ihrm.local'),
            occurredAt: new DateTimeImmutable('2026-07-01 00:00:00'),
        ));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'module' => 'identity',
            'entity_type' => 'user',
            'entity_id' => (string) $userId,
            'result' => 'success',
        ]);
    }

    public function test_maps_failed_login_event(): void
    {
        app(AuditEventListener::class)->handle(new UserLoginFailed(
            email: Email::fromString('missing@ihrm.local'),
            reason: 'Invalid credentials',
            occurredAt: new DateTimeImmutable('2026-07-01 00:00:00'),
        ));

        $log = AuditLogModel::firstOrFail();
        $this->assertSame('login_failed', $log->action);
        $this->assertSame('failure', $log->result);
        $this->assertSame('missing@ihrm.local', $log->after_payload['email']);
    }

    public function test_maps_role_and_permission_events(): void
    {
        $roleId = RoleId::generate();

        app(AuditEventListener::class)->handle(new RoleCreated(
            roleId: $roleId,
            code: RoleCode::fromString('HR_MANAGER'),
            occurredAt: new DateTimeImmutable('2026-07-01 00:00:00'),
        ));
        app(AuditEventListener::class)->handle(new RolePermissionGranted(
            roleId: $roleId,
            code: PermissionCode::fromString('identity.user.list'),
            occurredAt: new DateTimeImmutable('2026-07-01 00:00:00'),
        ));

        $this->assertDatabaseHas('audit_logs', ['action' => 'created', 'entity_type' => 'role', 'entity_id' => (string) $roleId]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'permission_granted', 'entity_type' => 'role', 'entity_id' => (string) $roleId]);
    }
}
