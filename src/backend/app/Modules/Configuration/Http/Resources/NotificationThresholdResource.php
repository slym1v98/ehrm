<?php

namespace App\Modules\Configuration\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationThresholdResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id'=>$this->id,'code'=>$this->code,'target_type'=>$this->target_type,'days_before'=>$this->days_before,'channel'=>$this->channel,'active'=>$this->active,'metadata'=>$this->metadata];
    }
}
