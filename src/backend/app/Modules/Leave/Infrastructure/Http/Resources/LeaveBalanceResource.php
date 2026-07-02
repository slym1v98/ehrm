<?php
namespace App\Modules\Leave\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class LeaveBalanceResource extends JsonResource { public function toArray($request): array { return ['id'=>$this->resource->id()->value(),'employee_id'=>$this->resource->employeeId(),'leave_type_id'=>$this->resource->leaveTypeId()->value(),'year'=>$this->resource->year(),'opening'=>$this->resource->opening(),'accrued'=>$this->resource->accrued(),'used'=>$this->resource->used(),'carried_over'=>$this->resource->carriedOver(),'expired'=>$this->resource->expired(),'remaining'=>$this->resource->remaining()]; } }
