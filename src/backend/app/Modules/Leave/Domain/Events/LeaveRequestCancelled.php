<?php

namespace App\Modules\Leave\Domain\Events;

class LeaveRequestCancelled
{
    public function __construct(public readonly array $payload)
    {
    }
}
