<?php

namespace App\Modules\Configuration\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CodeGenerationRuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id'=>$this->id,'entity_type'=>$this->entity_type,'prefix'=>$this->prefix,'pattern'=>$this->pattern,'next_number'=>$this->next_number,'sequence_padding'=>$this->sequence_padding,'active'=>$this->active];
    }
}
