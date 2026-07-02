<?php

namespace App\Modules\Attendance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAttendanceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'attendance_timesheet_id' => ['required', 'uuid'],
            'employee_id' => ['required', 'uuid'],
            'requested_by' => ['nullable', 'uuid'],
            'corrections' => ['required', 'array', 'min:1'],
            'reason' => ['required', 'string'],
            'evidence_file' => ['nullable', 'string', 'max:500'],
        ];
    }
}
