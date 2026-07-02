<?php

namespace App\Modules\Workflow\Application\QueryHandlers;

use App\Modules\Workflow\Application\Queries\GetWorkflowRequestQuery;
use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowRequestId;
use App\Modules\Workflow\Domain\Exceptions\WorkflowRequestNotFoundException;
use App\Modules\Workflow\Domain\Repositories\WorkflowRequestRepositoryInterface;

class GetWorkflowRequestHandler
{
    public function __construct(private WorkflowRequestRepositoryInterface $requests) {}
    public function handle(GetWorkflowRequestQuery $query)
    {
        $request = $this->requests->findById(new WorkflowRequestId($query->id));
        if (! $request) throw new WorkflowRequestNotFoundException();
        return $request;
    }
}
