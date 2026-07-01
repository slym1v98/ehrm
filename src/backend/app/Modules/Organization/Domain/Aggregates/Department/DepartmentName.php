<?php

namespace App\Modules\Organization\Domain\Aggregates\Department;

use InvalidArgumentException;

final readonly class DepartmentName
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Department name must not be empty');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('Department name too long (max 255)');
        }

        return new self($trimmed);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
