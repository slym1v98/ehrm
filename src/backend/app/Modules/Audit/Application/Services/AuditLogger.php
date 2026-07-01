<?php

namespace App\Modules\Audit\Application\Services;

use App\Modules\Audit\Domain\Events\AuditLogged;
use App\Modules\Audit\Infrastructure\Persistence\Eloquent\AuditLogModel;
use DateTimeInterface;
use Illuminate\Support\Facades\Event;

class AuditLogger
{
    private const REDACTED = '[REDACTED]';
    private const SENSITIVE_KEYS = ['password', 'password_hash', 'token', 'access_token', 'refresh_token', 'secret', 'api_key'];

    public function log(
        string $action,
        string $module,
        string $entityType,
        ?string $entityId = null,
        ?string $actorUserId = null,
        ?array $beforePayload = null,
        ?array $afterPayload = null,
        string $result = 'success',
        ?DateTimeInterface $occurredAt = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditLogModel {
        $log = AuditLogModel::create([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'module' => $module,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_payload' => $this->redact($beforePayload),
            'after_payload' => $this->redact($afterPayload),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'result' => $result,
            'occurred_at' => $occurredAt ?? now(),
        ]);

        Event::dispatch(new AuditLogged($log->id, $action, $module, $entityType, $entityId, $result, $log->occurred_at));

        return $log;
    }

    private function redact(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $redacted = [];
        foreach ($payload as $key => $value) {
            $redacted[$key] = in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)
                ? self::REDACTED
                : (is_array($value) ? $this->redact($value) : $value);
        }

        return $redacted;
    }
}
