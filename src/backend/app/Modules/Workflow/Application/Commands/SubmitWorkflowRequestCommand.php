<?php

namespace App\Modules\Workflow\Application\Commands;

class SubmitWorkflowRequestCommand
{
    public function __construct(
        public readonly string $workflowTemplateId,
        public readonly string $subjectType,
        public readonly string $subjectId,
        public readonly string $submittedBy,
    ) {}
}
