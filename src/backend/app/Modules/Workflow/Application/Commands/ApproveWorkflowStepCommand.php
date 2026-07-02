<?php

namespace App\Modules\Workflow\Application\Commands;

class ApproveWorkflowStepCommand
{
    public function __construct(
        public readonly string $workflowRequestId,
        public readonly string $actorId,
        public readonly ?string $comment,
    ) {}
}
