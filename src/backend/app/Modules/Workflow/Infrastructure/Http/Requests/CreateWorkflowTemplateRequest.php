<?php

namespace App\Modules\Workflow\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWorkflowTemplateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:80',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.step_order' => 'required|integer|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.assignee_type' => 'required|in:role,department,specific_user',
            'steps.*.assignee_id' => 'nullable|uuid',
            'steps.*.condition' => 'nullable|array',
        ];
    }
}
