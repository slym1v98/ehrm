<?php

namespace App\Modules\Workflow\Application\CommandHandlers;

use App\Modules\Workflow\Application\Commands\CreateWorkflowTemplateCommand;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowStep;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowStepId;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplate;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplateId;
use App\Modules\Workflow\Domain\Repositories\WorkflowTemplateRepositoryInterface;
use App\Modules\Workflow\Domain\ValueObjects\AssigneeType;

class CreateWorkflowTemplateHandler
{
    public function __construct(private WorkflowTemplateRepositoryInterface $templates) {}

    public function handle(CreateWorkflowTemplateCommand $command): WorkflowTemplate
    {
        $steps = array_map(fn (array $s) => new WorkflowStep(
            WorkflowStepId::new(),
            (int) $s['step_order'],
            $s['name'],
            AssigneeType::from($s['assignee_type']),
            $s['assignee_id'] ?? null,
            $s['condition'] ?? null,
        ), $command->steps);

        $template = new WorkflowTemplate(WorkflowTemplateId::new(), $command->code, $command->name, $command->description, true, $steps);
        $this->templates->save($template);
        return $template;
    }
}
