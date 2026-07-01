<?php

namespace App\Modules\Identity\Domain\Aggregates\User;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final readonly class EmployeeId
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (! Uuid::isValid($value)) {
            throw new InvalidArgumentException("Invalid EmployeeId: {$value}");
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
