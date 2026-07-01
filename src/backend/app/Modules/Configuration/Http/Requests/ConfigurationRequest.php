<?php

namespace App\Modules\Configuration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfigurationRequest extends FormRequest
{
    public function rules(): array
    {
        return match ($this->route()?->getName()) {
            'config.code_rules.store' => ['entity_type'=>['required','string','max:100'],'prefix'=>['required','string','max:20'],'pattern'=>['required','string','max:255'],'next_number'=>['integer','min:1'],'sequence_padding'=>['integer','min:1','max:12'],'active'=>['boolean']],
            'config.settings.store' => ['key'=>['required','string','max:150'],'value'=>['nullable','string'],'value_type'=>['string','max:30'],'group'=>['nullable','string','max:100'],'description'=>['nullable','string'],'editable'=>['boolean']],
            'config.holidays.store' => ['code'=>['required','string','max:100'],'name'=>['required','string','max:255'],'year'=>['required','integer','min:1900'],'active'=>['boolean']],
            'config.holidays.value.store' => ['date'=>['required','date'],'name'=>['required','string','max:255'],'type'=>['string','max:50'],'paid'=>['boolean'],'metadata'=>['nullable','array']],
            'config.thresholds.store' => ['code'=>['required','string','max:100'],'target_type'=>['required','string','max:100'],'days_before'=>['required','integer','min:0'],'channel'=>['string','max:50'],'active'=>['boolean'],'metadata'=>['nullable','array']],
            default => [],
        };
    }
}
