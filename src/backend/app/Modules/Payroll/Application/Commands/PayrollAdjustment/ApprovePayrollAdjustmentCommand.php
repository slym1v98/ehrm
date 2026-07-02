<?php
namespace App\Modules\Payroll\Application\Commands\PayrollAdjustment;
readonly class ApprovePayrollAdjustmentCommand
{
    public function __construct(public string $adjustmentId, public string $approvedBy) {}
}
