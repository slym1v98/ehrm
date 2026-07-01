<?php

namespace App\Modules\Configuration\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id'=>$this->id,'key'=>$this->key,'value'=>$this->value,'value_type'=>$this->value_type,'group'=>$this->group,'description'=>$this->description,'editable'=>$this->editable];
    }
}
