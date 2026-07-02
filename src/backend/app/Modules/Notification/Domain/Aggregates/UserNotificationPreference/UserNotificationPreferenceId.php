<?php

namespace App\Modules\Notification\Domain\Aggregates\UserNotificationPreference;

use Ramsey\Uuid\Uuid;

readonly class UserNotificationPreferenceId
{
    public function __construct(public string $value) {}

    public static function generate(): self
    {
        return new self(Uuid::uuid7()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
