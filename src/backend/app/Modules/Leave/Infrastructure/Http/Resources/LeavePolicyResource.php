<?php
namespace App\Modules\Leave\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class LeavePolicyResource extends JsonResource { public function toArray($request): array { return ['id'=>$this->resource->id()->value(),'leave_type_id'=>$this->resource->leaveTypeId()->value(),'valid_from'=>$this->resource->validFrom()->toDateString(),'valid_until'=>$this->resource->validUntil()?->toDateString(),'max_consecutive_days'=>$this->resource->maxConsecutiveDays(),'requires_attachment'=>$this->resource->requiresAttachment(),'carry_over_limit'=>$this->resource->carryOverLimit(),'carry_over_expiry_months'=>$this->resource->carryOverExpiryMonths(),'half_day_allowed'=>$this->resource->halfDayAllowed(),'hourly_allowed'=>$this->resource->hourlyAllowed()]; } }
