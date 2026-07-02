<?php

namespace App\Modules\Payroll\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPayrollAdjustmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'component_id' => 'nullable|uuid|exists:payroll_components,id',
            'adjustment_type' => 'required|in:add,subtract,override',
            'amount' => 'required|numeric',
            'reason' => 'required|string|max:500',
        ];
    }
}
