<?php

namespace App\Modules\Workflow\Application\QueryHandlers;

use App\Modules\Workflow\Application\Queries\ListWorkflowRequestsQuery;
use App\Modules\Workflow\Domain\Repositories\WorkflowRequestRepositoryInterface;

class ListWorkflowRequestsHandler
{
    public function __construct(private WorkflowRequestRepositoryInterface $requests) {}
    public function handle(ListWorkflowRequestsQuery $query): array
    {
        if ($query->status) return $this->requests->findByStatus($query->status);
        if ($query->subjectType && $query->subjectId) return $this->requests->findBySubject($query->subjectType, $query->subjectId);
        return []; // basic impl
    }
}
