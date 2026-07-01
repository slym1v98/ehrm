<?php

namespace App\Modules\Identity\Domain\Events;

use App\Modules\Identity\Domain\Aggregates\User\Email;
use App\Modules\Identity\Domain\Aggregates\User\UserId;
use DateTimeImmutable;

final readonly class UserCreated
{
    public function __construct(
        public UserId $userId,
        public Email $email,
        public DateTimeImmutable $occurredAt,
    ) {}
}
