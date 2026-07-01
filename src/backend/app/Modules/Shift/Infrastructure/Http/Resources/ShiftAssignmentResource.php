<?php

namespace App\Modules\Shift\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShiftAssignmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'shift_template_id' => $this->shift_template_id,
            'assignable_type' => $this->assignable_type,
            'assignable_id' => $this->assignable_id,
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_to' => $this->effective_to?->format('Y-m-d'),
            'recurrence_rule' => $this->recurrence_rule,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
