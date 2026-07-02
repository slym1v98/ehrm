<?php

namespace App\Modules\Attendance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenAttendancePeriodRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'period_code' => ['required', 'string', 'max:20'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
