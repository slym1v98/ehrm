<?php

namespace App\Modules\Workflow\Domain\Events;

class WorkflowCancelled
{
    public function __construct(public readonly array $payload)
    {
    }
}
