<?php

namespace App\Modules\Identity\Domain\Aggregates\Role;

use DateTimeImmutable;

final readonly class RolePermission
{
    public function __construct(
        public PermissionCode $permissionCode,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
