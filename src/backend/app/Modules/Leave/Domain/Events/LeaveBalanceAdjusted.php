<?php

namespace App\Modules\Leave\Domain\Events;

class LeaveBalanceAdjusted
{
    public function __construct(public readonly array $payload)
    {
    }
}
