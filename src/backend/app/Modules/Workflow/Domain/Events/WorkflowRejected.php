<?php

namespace App\Modules\Workflow\Domain\Events;

class WorkflowRejected
{
    public function __construct(public readonly array $payload)
    {
    }
}
