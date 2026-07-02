<?php

namespace App\Modules\Attendance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'uuid'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ];
    }
}
