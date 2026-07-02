<?php

namespace App\Modules\Payroll\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'entry_id' => $this->entry_id,
            'employee_id' => $this->employee_id,
            'period_id' => $this->period_id,
            'gross' => $this->gross,
            'deductions' => $this->deductions,
            'net' => $this->net,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'access_count' => $this->access_count,
            'payload' => $this->payload,
        ];
    }
}
