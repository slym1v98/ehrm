<?php
namespace App\Modules\Leave\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class LeaveTypeResource extends JsonResource { public function toArray($request): array { return ['id'=>$this->resource->id()->value(),'name'=>$this->resource->name(),'code'=>$this->resource->code(),'is_balance_tracked'=>$this->resource->isBalanceTracked(),'is_active'=>$this->resource->isActive(),'sort_order'=>$this->resource->sortOrder()]; } }
