<?php

namespace App\Modules\Payroll\Domain\Aggregates\PayrollEntry;

use Ramsey\Uuid\Uuid;

readonly class PayrollEntryId
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

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
