<?php

namespace App\Modules\Identity\Domain\Aggregates\Role;

use InvalidArgumentException;

final readonly class PermissionCode
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if (! preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $trimmed)) {
            throw new InvalidArgumentException("Invalid permission code: {$value}");
        }
        if (mb_strlen($trimmed) > 150) {
            throw new InvalidArgumentException('Permission code too long (max 150)');
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
