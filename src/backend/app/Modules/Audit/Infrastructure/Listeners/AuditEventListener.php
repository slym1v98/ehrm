<?php

namespace App\Modules\Audit\Infrastructure\Listeners;

use App\Modules\Audit\Application\Services\AuditLogger;
use App\Modules\Identity\Domain\Events\RoleCreated;
use App\Modules\Identity\Domain\Events\RolePermissionGranted;
use App\Modules\Identity\Domain\Events\RolePermissionRevoked;
use App\Modules\Identity\Domain\Events\RoleUpdated;
use App\Modules\Identity\Domain\Events\UserCreated;
use App\Modules\Identity\Domain\Events\UserDataScopeGranted;
use App\Modules\Identity\Domain\Events\UserDisabled;
use App\Modules\Identity\Domain\Events\UserLoggedIn;
use App\Modules\Identity\Domain\Events\UserLoginFailed;
use App\Modules\Identity\Domain\Events\UserPasswordChanged;
use App\Modules\Identity\Domain\Events\UserReactivated;
use App\Modules\Identity\Domain\Events\UserRoleAssigned;
use App\Modules\Identity\Domain\Events\UserRoleRevoked;
use Illuminate\Support\Facades\Log;

class AuditEventListener
{
    public function __construct(private AuditLogger $logger) {}

    public function handle(object $event): void
    {
        $data = $this->map($event);
        if ($data === null) {
            return;
        }

        try {
            $this->logger->log(
                action: $data['action'],
                module: 'identity',
                entityType: $data['entity_type'],
                entityId: $data['entity_id'],
                actorUserId: $data['actor_user_id'] ?? (auth()->id() ? (string) auth()->id() : null),
                beforePayload: $data['before_payload'] ?? null,
                afterPayload: $data['after_payload'] ?? null,
                result: $data['result'],
                occurredAt: $event->occurredAt ?? now(),
                ipAddress: request()?->ip(),
                userAgent: request()?->userAgent(),
            );
        } catch (\Throwable $exception) {
            Log::warning('Audit write failed.', ['event' => $event::class, 'message' => $exception->getMessage()]);
        }
    }

    private function map(object $event): ?array
    {
        return match ($event::class) {
            UserCreated::class => ['action' => 'created', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success', 'after_payload' => ['email' => (string) $event->email]],
            UserLoggedIn::class => ['action' => 'login', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success'],
            UserLoginFailed::class => ['action' => 'login_failed', 'entity_type' => 'user', 'entity_id' => null, 'result' => 'failure', 'after_payload' => ['email' => (string) $event->email, 'reason' => $event->reason]],
            UserDisabled::class => ['action' => 'disabled', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success'],
            UserReactivated::class => ['action' => 'reactivated', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success'],
            UserPasswordChanged::class => ['action' => 'password_changed', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success'],
            UserRoleAssigned::class => ['action' => 'role_assigned', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'actor_user_id' => $event->assignedBy ? (string) $event->assignedBy : null, 'result' => 'success', 'after_payload' => ['role_id' => (string) $event->roleId]],
            UserRoleRevoked::class => ['action' => 'role_revoked', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success', 'after_payload' => ['role_id' => (string) $event->roleId]],
            UserDataScopeGranted::class => ['action' => 'data_scope_granted', 'entity_type' => 'user', 'entity_id' => (string) $event->userId, 'result' => 'success', 'after_payload' => ['scope_type' => $event->scope->type->value]],
            RoleCreated::class => ['action' => 'created', 'entity_type' => 'role', 'entity_id' => (string) $event->roleId, 'result' => 'success', 'after_payload' => ['code' => (string) $event->code]],
            RoleUpdated::class => ['action' => 'updated', 'entity_type' => 'role', 'entity_id' => (string) $event->roleId, 'result' => 'success'],
            RolePermissionGranted::class => ['action' => 'permission_granted', 'entity_type' => 'role', 'entity_id' => (string) $event->roleId, 'result' => 'success', 'after_payload' => ['permission_code' => (string) $event->code]],
            RolePermissionRevoked::class => ['action' => 'permission_revoked', 'entity_type' => 'role', 'entity_id' => (string) $event->roleId, 'result' => 'success', 'after_payload' => ['permission_code' => (string) $event->code]],
            default => null,
        };
    }
}
