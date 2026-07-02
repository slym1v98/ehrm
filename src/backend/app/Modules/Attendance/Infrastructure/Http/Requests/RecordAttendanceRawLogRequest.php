<?php

namespace App\Modules\Attendance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordAttendanceRawLogRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'uuid'],
            'source' => ['required', 'in:web,manual,import,device,gps'],
            'event_type' => ['required', 'in:check_in,check_out,manual'],
            'event_time' => ['required', 'date'],
            'geo_point' => ['nullable', 'array'],
            'geo_point.lat' => ['required_with:geo_point', 'numeric', 'between:-90,90'],
            'geo_point.lng' => ['required_with:geo_point', 'numeric', 'between:-180,180'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
