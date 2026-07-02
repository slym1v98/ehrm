<?php

namespace App\Modules\Workflow\Domain\Events;

class WorkflowStepCompleted
{
    public function __construct(public readonly array $payload)
    {
    }
}
