<?php

namespace App\Modules\Workflow\Domain\Events;

class WorkflowReturnedForEdit
{
    public function __construct(public readonly array $payload)
    {
    }
}
