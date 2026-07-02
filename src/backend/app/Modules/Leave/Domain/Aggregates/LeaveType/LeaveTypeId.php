<?php

namespace App\Modules\Leave\Domain\Aggregates\LeaveType;

use Ramsey\Uuid\Uuid;

class LeaveTypeId
{
    public function __construct(private string $value)
    {
        if ($value === '' || ! Uuid::isValid($value)) {
            throw new \InvalidArgumentException('LeaveTypeId must be a valid UUID');
        }
    }

    public static function new(): self
    {
        return new self((string) Uuid::uuid4());
    }

    public function value(): string
    {
        return $this->value;
    }
}
