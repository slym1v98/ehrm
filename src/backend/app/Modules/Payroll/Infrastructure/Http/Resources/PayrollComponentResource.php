<?php

namespace App\Modules\Payroll\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollComponentResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id'=>$this->id,'code'=>$this->code,'name'=>$this->name,'category'=>$this->category,'calculation_type'=>$this->calculation_type,'percent_base_component_id'=>$this->percent_base_component_id,'default_amount'=>$this->default_amount,'default_percent'=>$this->default_percent,'taxable'=>(bool)$this->taxable,'active'=>(bool)$this->active];
    }
}
