<?php

namespace App\Modules\Configuration\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HolidayCalendarResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id'=>$this->id,'code'=>$this->code,'name'=>$this->name,'year'=>$this->year,'active'=>$this->active,'holidays'=>HolidayResource::collection($this->whenLoaded('holidays'))];
    }
}
