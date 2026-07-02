<?php

namespace App\Modules\Workflow\Domain\Events;

class WorkflowRequestStarted
{
    public function __construct(public readonly array $payload)
    {
    }
}
