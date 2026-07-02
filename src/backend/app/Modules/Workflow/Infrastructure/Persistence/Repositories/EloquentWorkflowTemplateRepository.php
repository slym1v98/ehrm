<?php

namespace App\Modules\Workflow\Infrastructure\Persistence\Repositories;

use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowStep;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowStepId;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplate;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplateId;
use App\Modules\Workflow\Domain\Repositories\WorkflowTemplateRepositoryInterface;
use App\Modules\Workflow\Domain\ValueObjects\AssigneeType;
use App\Modules\Workflow\Infrastructure\Persistence\Eloquent\WorkflowTemplateModel;
use App\Modules\Workflow\Infrastructure\Persistence\Eloquent\WorkflowTemplateStepModel;
use Carbon\CarbonImmutable;
use Ramsey\Uuid\Uuid;

class EloquentWorkflowTemplateRepository implements WorkflowTemplateRepositoryInterface
{
    public function findById(WorkflowTemplateId $id): ?WorkflowTemplate
    {
        $model = WorkflowTemplateModel::with('steps')->find($id->value());
        return $model ? $this->toDomain($model) : null;
    }

    public function findByCode(string $code): ?WorkflowTemplate
    {
        $model = WorkflowTemplateModel::with('steps')->where('code', $code)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function allActive(): array
    {
        return WorkflowTemplateModel::with('steps')->where('active', true)->get()->map(fn ($m) => $this->toDomain($m))->all();
    }

    public function save(WorkflowTemplate $template): void
    {
        $tModel = WorkflowTemplateModel::updateOrCreate(
            ['id' => $template->id()->value()],
            ['code' => $template->code(), 'name' => $template->name(), 'description' => $template->description(), 'active' => $template->isActive()],
        );
        foreach ($template->steps() as $step) {
            WorkflowTemplateStepModel::updateOrCreate(
                ['workflow_template_id' => $tModel->id, 'step_order' => $step->stepOrder()],
                ['id' => $step->id()->value(), 'name' => $step->name(), 'assignee_type' => $step->assigneeType()->value, 'assignee_id' => $step->assigneeId(), 'condition' => $step->condition()],
            );
        }
    }

    private function toDomain(WorkflowTemplateModel $model): WorkflowTemplate
    {
        $steps = $model->steps->map(fn (WorkflowTemplateStepModel $s) => new WorkflowStep(
            new WorkflowStepId($s->id),
            $s->step_order,
            $s->name,
            AssigneeType::from($s->assignee_type),
            $s->assignee_id,
            $s->condition,
        ))->all();
        return new WorkflowTemplate(
            new WorkflowTemplateId($model->id),
            $model->code,
            $model->name,
            $model->description,
            $model->active,
            $steps,
        );
    }
}
