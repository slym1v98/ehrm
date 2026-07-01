<?php

namespace App\Modules\Identity\Domain\Aggregates\Role;

use InvalidArgumentException;

final readonly class RoleCode
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $trimmed)) {
            throw new InvalidArgumentException("Invalid role code: {$value}");
        }
        if (mb_strlen($trimmed) > 100) {
            throw new InvalidArgumentException('Role code too long (max 100)');
        }

        return new self($trimmed);
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
