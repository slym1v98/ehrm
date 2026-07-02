<?php

namespace App\Modules\Payroll\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollComponentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100',
            'default_amount' => 'sometimes|nullable|numeric',
            'default_percent' => 'sometimes|nullable|numeric|min:0|max:100',
            'taxable' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
        ];
    }
}
