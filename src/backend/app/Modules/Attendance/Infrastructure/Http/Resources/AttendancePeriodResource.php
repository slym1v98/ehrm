<?php

namespace App\Modules\Attendance\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendancePeriodResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'period_code' => $this->period_code,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
