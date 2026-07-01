<?php

namespace App\Modules\Configuration\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HolidayResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id'=>$this->id,'date'=>$this->date?->format('Y-m-d'),'name'=>$this->name,'type'=>$this->type,'paid'=>$this->paid,'metadata'=>$this->metadata];
    }
}
