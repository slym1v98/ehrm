<?php

namespace App\Modules\Identity\Domain\Aggregates\User;

use InvalidArgumentException;

final readonly class Email
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = mb_strtolower(trim($value));
        if (! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }

        return new self($normalized);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
