<?php

namespace App\Modules\Workflow\Domain\Events;

class WorkflowApproved
{
    public function __construct(public readonly array $payload)
    {
    }
}
