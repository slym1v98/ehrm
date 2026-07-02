<?php

namespace App\Modules\Workflow\Application\QueryHandlers;

use App\Modules\Workflow\Application\Queries\GetWorkflowTemplateQuery;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplateId;
use App\Modules\Workflow\Domain\Exceptions\WorkflowTemplateNotFoundException;
use App\Modules\Workflow\Domain\Repositories\WorkflowTemplateRepositoryInterface;

class GetWorkflowTemplateHandler
{
    public function __construct(private WorkflowTemplateRepositoryInterface $templates) {}
    public function handle(GetWorkflowTemplateQuery $query)
    {
        $template = $this->templates->findById(new WorkflowTemplateId($query->id));
        if (! $template) throw new WorkflowTemplateNotFoundException();
        return $template;
    }
}
