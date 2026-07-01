<?php

namespace App\Modules\Identity\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
            'scope_type' => ['required', 'in:self,direct_reports,department,branch,all_company'],
            'branch_id' => ['required_if:scope_type,branch', 'nullable', 'uuid'],
            'department_id' => ['required_if:scope_type,department', 'nullable', 'uuid'],
        ];
    }
}
