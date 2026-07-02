<?php

namespace App\Modules\Attendance\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRawLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'source' => $this->source,
            'event_type' => $this->event_type,
            'event_time' => $this->event_time,
            'geo_point' => $this->geo_point,
            'payload' => $this->payload,
            'created_at' => $this->created_at,
        ];
    }
}
