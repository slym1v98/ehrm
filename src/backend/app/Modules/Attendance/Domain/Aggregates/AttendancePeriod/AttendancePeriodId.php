<?php

namespace App\Modules\Attendance\Domain\Aggregates\AttendancePeriod;

use Illuminate\Support\Str;

final class AttendancePeriodId
{
    private function __construct(private readonly string $value) {}

    public static function generate(): self { return new self((string) Str::uuid()); }
    public static function fromString(string $value): self { return new self($value); }
    public function toString(): string { return $this->value; }
    public function __toString(): string { return $this->value; }
}
