<?php

namespace App\Modules\Identity\Domain\Events;

use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use DateTimeImmutable;

final readonly class RoleUpdated
{
    public function __construct(public RoleId $roleId, public DateTimeImmutable $occurredAt) {}
}
