<?php

namespace App\Modules\Workflow\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartWorkflowRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'workflow_template_id' => 'required|uuid',
            'subject_type' => 'required|string|max:80',
            'subject_id' => 'required|uuid',
        ];
    }
}
