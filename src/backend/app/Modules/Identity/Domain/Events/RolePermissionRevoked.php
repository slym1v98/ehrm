<?php

namespace App\Modules\Identity\Domain\Events;

use App\Modules\Identity\Domain\Aggregates\Role\PermissionCode;
use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use DateTimeImmutable;

final readonly class RolePermissionRevoked
{
    public function __construct(public RoleId $roleId, public PermissionCode $code, public DateTimeImmutable $occurredAt) {}
}
