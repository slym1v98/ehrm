<?php

namespace App\Modules\Payroll\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollComponentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:payroll_components,code',
            'name' => 'required|string|max:100',
            'category' => 'required|in:base,allowance,bonus,penalty,overtime,deduction,insurance,tax,net',
            'calculation_type' => 'required|in:fixed_amount,percent_of_component,manual_entry',
            'percent_base_component_id' => 'nullable|uuid|exists:payroll_components,id|required_if:calculation_type,percent_of_component',
            'default_amount' => 'nullable|numeric|required_if:calculation_type,fixed_amount',
            'default_percent' => 'nullable|numeric|min:0|max:100',
            'taxable' => 'sometimes|boolean',
        ];
    }
}
