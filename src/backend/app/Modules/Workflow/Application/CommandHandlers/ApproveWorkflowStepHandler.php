<?php

namespace App\Modules\Workflow\Application\CommandHandlers;

use App\Modules\Workflow\Application\Commands\ApproveWorkflowStepCommand;
use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowRequestId;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplateId;
use App\Modules\Workflow\Domain\Exceptions\WorkflowRequestNotFoundException;
use App\Modules\Workflow\Domain\Exceptions\WorkflowTemplateNotFoundException;
use App\Modules\Workflow\Domain\Repositories\WorkflowRequestRepositoryInterface;
use App\Modules\Workflow\Domain\Repositories\WorkflowTemplateRepositoryInterface;

class ApproveWorkflowStepHandler
{
    public function __construct(private WorkflowRequestRepositoryInterface $requests, private WorkflowTemplateRepositoryInterface $templates) {}

    public function handle(ApproveWorkflowStepCommand $command): void
    {
        $request = $this->requests->findById(new WorkflowRequestId($command->workflowRequestId));
        if (! $request) throw new WorkflowRequestNotFoundException();
        $template = $this->templates->findById($request->workflowTemplateId());
        if (! $template) throw new WorkflowTemplateNotFoundException();
        $currentStep = $request->currentStep() ?? 0;
        $isFinal = $template->isFinalStep($currentStep);
        $request->approveStep($command->actorId, $currentStep, $isFinal, $command->comment);
        $this->requests->save($request);
    }
}
