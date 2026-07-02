<?php

namespace App\Modules\Workflow\Application\CommandHandlers;

use App\Modules\Workflow\Application\Commands\CancelWorkflowRequestCommand;
use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowRequestId;
use App\Modules\Workflow\Domain\Exceptions\WorkflowRequestNotFoundException;
use App\Modules\Workflow\Domain\Repositories\WorkflowRequestRepositoryInterface;

class CancelWorkflowRequestHandler
{
    public function __construct(private WorkflowRequestRepositoryInterface $requests) {}

    public function handle(CancelWorkflowRequestCommand $command): void
    {
        $request = $this->requests->findById(new WorkflowRequestId($command->workflowRequestId));
        if (! $request) throw new WorkflowRequestNotFoundException();
        $request->cancel($command->actorId, $command->comment);
        $this->requests->save($request);
    }
}
