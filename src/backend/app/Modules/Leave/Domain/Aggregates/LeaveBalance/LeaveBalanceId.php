<?php

namespace App\Modules\Leave\Domain\Aggregates\LeaveBalance;

use Ramsey\Uuid\Uuid;

class LeaveBalanceId
{
    public function __construct(private string $value)
    {
        if ($value === '' || ! Uuid::isValid($value)) {
            throw new \InvalidArgumentException('LeaveBalanceId must be a valid UUID');
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
