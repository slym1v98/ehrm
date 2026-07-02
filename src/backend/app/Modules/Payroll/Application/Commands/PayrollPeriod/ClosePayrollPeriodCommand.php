<?php
namespace App\Modules\Payroll\Application\Commands\PayrollPeriod;
readonly class ClosePayrollPeriodCommand
{
    public function __construct(public string $periodId, public string $closedBy) {}
}
