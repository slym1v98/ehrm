<?php

namespace App\Modules\Workflow\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowRequestResource extends JsonResource
{
    public function toArray($request)
    {
        $r = $this->resource;
        return [
            'id' => $r->id()->value(),
            'workflow_template_id' => $r->workflowTemplateId()->value(),
            'subject_type' => $r->subjectType(),
            'subject_id' => $r->subjectId(),
            'submitted_by' => $r->submittedBy(),
            'status' => $r->status()->value,
            'current_step' => $r->currentStep(),
            'actions' => array_map(fn ($a) => [
                'id' => $a->id()->value(),
                'step_order' => $a->stepOrder(),
                'action' => $a->action()->value,
                'actor_id' => $a->actorId(),
                'comment' => $a->comment(),
                'created_at' => $a->createdAt()->toIso8601String(),
            ], $r->actions()),
        ];
    }
}
