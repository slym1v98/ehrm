<?php
namespace App\Modules\Payroll\Application\Commands\PayrollAdjustment;
readonly class RejectPayrollAdjustmentCommand
{
    public function __construct(public string $adjustmentId, public string $rejectedBy, public string $reason) {}
}
