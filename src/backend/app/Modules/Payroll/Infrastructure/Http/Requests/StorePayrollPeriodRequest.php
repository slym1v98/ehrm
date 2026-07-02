<?php

namespace App\Modules\Payroll\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'period_code' => 'required|string|max:20|unique:payroll_periods,period_code',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'cutoff_date' => 'required|date',
            'attendance_period_id' => 'nullable|uuid|exists:attendance_periods,id',
        ];
    }
}
