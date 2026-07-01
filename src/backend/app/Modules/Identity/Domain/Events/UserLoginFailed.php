<?php

namespace App\Modules\Identity\Domain\Events;

use App\Modules\Identity\Domain\Aggregates\User\Email;
use DateTimeImmutable;

final readonly class UserLoginFailed
{
    public function __construct(public Email $email, public string $reason, public DateTimeImmutable $occurredAt) {}
}
