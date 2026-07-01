<?php

namespace App\Modules\Identity\Domain\Events;

use App\Modules\Identity\Domain\Aggregates\Role\RoleCode;
use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use DateTimeImmutable;

final readonly class RoleCreated
{
    public function __construct(public RoleId $roleId, public RoleCode $code, public DateTimeImmutable $occurredAt) {}
}
