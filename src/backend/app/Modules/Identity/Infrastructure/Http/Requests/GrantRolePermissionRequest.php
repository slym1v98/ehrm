<?php

namespace App\Modules\Identity\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GrantRolePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'permission_code' => ['required', 'string', 'exists:permissions,code'],
        ];
    }
}
