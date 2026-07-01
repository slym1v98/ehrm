<?php

namespace App\Modules\Identity\Domain\Aggregates\Role;

use InvalidArgumentException;

final readonly class RoleName
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Role name must not be empty');
        }
        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('Role name too long (max 255)');
        }

        return new self($trimmed);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
