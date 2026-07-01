<?php

namespace App\Modules\Identity\Domain\Events;

use App\Modules\Identity\Domain\Aggregates\User\UserId;
use DateTimeImmutable;

final readonly class UserDisabled
{
    public function __construct(public UserId $userId, public DateTimeImmutable $occurredAt) {}
}
