<?php

namespace App\Modules\Workflow\Application\QueryHandlers;

use App\Modules\Workflow\Application\Queries\ListWorkflowTemplatesQuery;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplate;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplateId;
use App\Modules\Workflow\Domain\Repositories\WorkflowTemplateRepositoryInterface;

class ListWorkflowTemplatesHandler
{
    public function __construct(private WorkflowTemplateRepositoryInterface $templates) {}
    public function handle(ListWorkflowTemplatesQuery $query): array
    {
        return $this->templates->allActive();
    }
}
