<?php

namespace App\Modules\Workflow\Infrastructure\Http\Resources;

use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowTemplateResource extends JsonResource
{
    public function toArray($request)
    {
        $template = $this->resource;
        return [
            'id' => $template->id()->value(),
            'code' => $template->code(),
            'name' => $template->name(),
            'description' => $template->description(),
            'active' => $template->isActive(),
            'steps' => array_map(fn ($s) => [
                'step_order' => $s->stepOrder(),
                'name' => $s->name(),
                'assignee_type' => $s->assigneeType()->value,
                'assignee_id' => $s->assigneeId(),
                'condition' => $s->condition(),
            ], $template->steps()),
        ];
    }
}
