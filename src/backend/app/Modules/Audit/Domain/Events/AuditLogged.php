<?php

namespace App\Modules\Audit\Domain\Events;

use DateTimeInterface;

final readonly class AuditLogged
{
    public function __construct(
        public string $auditLogId,
        public string $action,
        public string $module,
        public string $entityType,
        public ?string $entityId,
        public string $result,
        public DateTimeInterface $occurredAt,
    ) {}
}
