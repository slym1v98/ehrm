<?php

namespace App\Modules\Workflow\Application\CommandHandlers;

use App\Modules\Workflow\Application\Commands\SubmitWorkflowRequestCommand;
use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowRequest;
use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowRequestId;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplateId;
use App\Modules\Workflow\Domain\Exceptions\WorkflowTemplateNotFoundException;
use App\Modules\Workflow\Domain\Repositories\WorkflowRequestRepositoryInterface;
use App\Modules\Workflow\Domain\Repositories\WorkflowTemplateRepositoryInterface;

class SubmitWorkflowRequestHandler
{
    public function __construct(private WorkflowTemplateRepositoryInterface $templates, private WorkflowRequestRepositoryInterface $requests) {}

    public function handle(SubmitWorkflowRequestCommand $command): WorkflowRequest
    {
        $template = $this->templates->findById(new WorkflowTemplateId($command->workflowTemplateId));
        if (! $template || ! $template->isActive()) {
            throw new WorkflowTemplateNotFoundException('Workflow template not found');
        }
        $request = new WorkflowRequest(WorkflowRequestId::new(), $template->id(), $command->subjectType, $command->subjectId, $command->submittedBy);
        $request->start($template->firstStep()->stepOrder());
        $this->requests->save($request);
        return $request;
    }
}
