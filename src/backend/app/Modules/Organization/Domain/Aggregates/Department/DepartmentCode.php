<?php

namespace App\Modules\Organization\Domain\Aggregates\Department;

use App\Modules\Organization\Domain\Exceptions\InvalidOrganizationCodeException;

final readonly class DepartmentCode
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));

        if (! preg_match('/^[A-Z][A-Z0-9-]{1,49}$/', $normalized)) {
            throw new InvalidOrganizationCodeException('Department code must be uppercase alphanumeric with dash, 2-50 chars.');
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
