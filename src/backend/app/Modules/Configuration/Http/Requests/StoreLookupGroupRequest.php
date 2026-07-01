<?php

namespace App\Modules\Configuration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLookupGroupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
        ];
    }
}
