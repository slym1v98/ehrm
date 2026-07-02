<?php

namespace App\Modules\Payroll\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollPeriodResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id'=>$this->id,'period_code'=>$this->period_code,'start_date'=>$this->start_date,'end_date'=>$this->end_date,'cutoff_date'=>$this->cutoff_date,'status'=>$this->status,'workflow_request_id'=>$this->workflow_request_id,'opened_by'=>$this->opened_by,'opened_at'=>$this->opened_at,'approved_by'=>$this->approved_by,'approved_at'=>$this->approved_at,'locked_by'=>$this->locked_by,'locked_at'=>$this->locked_at,'published_at'=>$this->published_at,'created_at'=>$this->created_at,'updated_at'=>$this->updated_at];
    }
}
