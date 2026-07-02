<?php

namespace App\Modules\Leave\Domain\Events;

class LeaveRequestApproved
{
    public function __construct(public readonly array $payload)
    {
    }
}
