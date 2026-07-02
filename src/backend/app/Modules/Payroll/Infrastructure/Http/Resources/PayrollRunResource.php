<?php

namespace App\Modules\Payroll\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRunResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id'=>$this->id,'period_id'=>$this->period_id,'run_type'=>$this->run_type,'status'=>$this->status,'formula_version'=>$this->formula_version,'triggered_by'=>$this->triggered_by,'started_at'=>$this->started_at,'completed_at'=>$this->completed_at,'error_summary'=>$this->error_summary];
    }
}
