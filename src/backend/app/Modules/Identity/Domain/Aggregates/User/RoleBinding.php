<?php

namespace App\Modules\Identity\Domain\Aggregates\User;

use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use DateTimeImmutable;

final class RoleBinding
{
    public function __construct(
        public readonly RoleId $roleId,
        public readonly ?UserId $assignedBy,
        public readonly DateTimeImmutable $assignedAt,
        private ?DateTimeImmutable $revokedAt = null,
    ) {
    }

    public function revoke(DateTimeImmutable $at): void
    {
        $this->revokedAt ??= $at;
    }

    public function isActive(): bool
    {
        return $this->revokedAt === null;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }
}
