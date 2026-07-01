<?php

namespace App\Modules\Shift\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShiftTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_overnight' => $this->is_overnight,
            'break_minutes' => $this->break_minutes,
            'late_tolerance_minutes' => $this->late_tolerance_minutes,
            'overtime_rules' => $this->overtime_rules,
            'flexibility_rules' => $this->flexibility_rules,
            'payroll_attribution_rule' => $this->payroll_attribution_rule,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
