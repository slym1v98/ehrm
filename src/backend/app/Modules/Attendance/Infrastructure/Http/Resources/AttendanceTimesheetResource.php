<?php

namespace App\Modules\Attendance\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceTimesheetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'attendance_period_id' => $this->attendance_period_id,
            'employee_id' => $this->employee_id,
            'work_date' => $this->work_date,
            'shift_assignment_id' => $this->shift_assignment_id,
            'expected_minutes' => $this->expected_minutes,
            'worked_minutes' => $this->worked_minutes,
            'late_minutes' => $this->late_minutes,
            'early_leave_minutes' => $this->early_leave_minutes,
            'overtime_minutes' => $this->overtime_minutes,
            'result_status' => $this->result_status,
            'calculation_run_id' => $this->calculation_run_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
