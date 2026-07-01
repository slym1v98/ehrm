<?php

namespace App\Modules\Identity\Domain\Events;

use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use App\Modules\Identity\Domain\Aggregates\User\UserId;
use DateTimeImmutable;

final readonly class UserRoleRevoked
{
    public function __construct(public UserId $userId, public RoleId $roleId, public DateTimeImmutable $occurredAt) {}
}
