<?php

namespace App\Modules\Workflow\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecisionRequest extends FormRequest
{
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        $required = in_array($action, ['reject', 'returnForEdit']) ? 'required' : 'nullable';
        return ['comment' => "$required|string|max:2000"];
    }
}
