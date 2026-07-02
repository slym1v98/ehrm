<?php

namespace App\Modules\Attendance\Domain\Aggregates\AttendanceAdjustmentRequest;

use Illuminate\Support\Str;

final class AttendanceAdjustmentRequestId
{
    private function __construct(private readonly string $value) {}

    public static function generate(): self { return new self((string) Str::uuid()); }
    public static function fromString(string $value): self { return new self($value); }
    public function toString(): string { return $this->value; }
    public function __toString(): string { return $this->value; }
}
