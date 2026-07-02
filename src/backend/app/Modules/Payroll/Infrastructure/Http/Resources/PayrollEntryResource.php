<?php

namespace App\Modules\Payroll\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'run_id' => $this->run_id,
            'period_id' => $this->period_id,
            'employee_id' => $this->employee_id,
            'contract_snapshot' => $this->contract_snapshot,
            'attendance_snapshot' => $this->attendance_snapshot,
            'leave_snapshot' => $this->leave_snapshot,
            'gross_amount' => $this->gross_amount,
            'deduction_amount' => $this->deduction_amount,
            'net_amount' => $this->net_amount,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn($l) => [
                'component_id' => $l->component_id,
                'category' => $l->category,
                'amount' => $l->amount,
                'calculation_note' => $l->calculation_note,
            ])),
        ];
    }
}
