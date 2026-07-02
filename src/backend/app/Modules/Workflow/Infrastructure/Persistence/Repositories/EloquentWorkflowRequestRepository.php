<?php

namespace App\Modules\Workflow\Infrastructure\Persistence\Repositories;

use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowAction;
use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowActionId;
use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowRequest;
use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowRequestId;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplateId;
use App\Modules\Workflow\Domain\Repositories\WorkflowRequestRepositoryInterface;
use App\Modules\Workflow\Domain\ValueObjects\RequestStatus;
use App\Modules\Workflow\Domain\ValueObjects\WorkflowActionType;
use App\Modules\Workflow\Infrastructure\Persistence\Eloquent\WorkflowRequestActionModel;
use App\Modules\Workflow\Infrastructure\Persistence\Eloquent\WorkflowRequestModel;
use Carbon\CarbonImmutable;

class EloquentWorkflowRequestRepository implements WorkflowRequestRepositoryInterface
{
    public function findById(WorkflowRequestId $id): ?WorkflowRequest
    {
        $model = WorkflowRequestModel::with('actions')->find($id->value());
        return $model ? $this->toDomain($model) : null;
    }

    public function findBySubject(string $subjectType, string $subjectId): array
    {
        return WorkflowRequestModel::with('actions')->where('subject_type', $subjectType)->where('subject_id', $subjectId)->get()->map(fn ($m) => $this->toDomain($m))->all();
    }

    public function findByStatus(string $status): array
    {
        return WorkflowRequestModel::with('actions')->where('status', $status)->get()->map(fn ($m) => $this->toDomain($m))->all();
    }

    public function save(WorkflowRequest $request): void
    {
        WorkflowRequestModel::updateOrCreate(
            ['id' => $request->id()->value()],
            [
                'workflow_template_id' => $request->workflowTemplateId()->value(),
                'subject_type' => $request->subjectType(),
                'subject_id' => $request->subjectId(),
                'status' => $request->status()->value,
                'current_step' => $request->currentStep(),
                'submitted_by' => $request->submittedBy(),
            ],
        );
        foreach ($request->actions() as $action) {
            WorkflowRequestActionModel::updateOrCreate(
                ['id' => $action->id()->value()],
                [
                    'workflow_request_id' => $request->id()->value(),
                    'step_order' => $action->stepOrder(),
                    'action' => $action->action()->value,
                    'actor_id' => $action->actorId(),
                    'comment' => $action->comment(),
                    'metadata' => $action->metadata(),
                    'created_at' => $action->createdAt(),
                ],
            );
        }
    }

    private function toDomain(WorkflowRequestModel $model): WorkflowRequest
    {
        $actions = $model->actions->map(fn (WorkflowRequestActionModel $a) => new WorkflowAction(
            new WorkflowActionId($a->id),
            new WorkflowRequestId($a->workflow_request_id),
            $a->step_order,
            WorkflowActionType::from($a->action),
            $a->actor_id,
            $a->comment,
            $a->metadata ?? [],
            $a->created_at ? CarbonImmutable::parse($a->created_at) : null,
        ))->all();
        return new WorkflowRequest(
            new WorkflowRequestId($model->id),
            new WorkflowTemplateId($model->workflow_template_id),
            $model->subject_type,
            $model->subject_id,
            $model->submitted_by,
            RequestStatus::tryFrom($model->status),
            $model->current_step,
            $actions,
        );
    }
}
