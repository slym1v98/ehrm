<?php

namespace App\Modules\Leave\Domain\Events;

class LeaveRequestSubmitted
{
    public function __construct(public readonly array $payload)
    {
    }
}
