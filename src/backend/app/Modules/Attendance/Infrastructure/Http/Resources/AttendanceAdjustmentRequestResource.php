<?php

namespace App\Modules\Attendance\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceAdjustmentRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'attendance_timesheet_id' => $this->attendance_timesheet_id,
            'employee_id' => $this->employee_id,
            'requested_by' => $this->requested_by,
            'reason' => $this->reason,
            'evidence_file' => $this->evidence_file,
            'corrections' => $this->corrections,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
