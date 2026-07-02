<?php
namespace App\Modules\Payroll\Application\Commands\PayrollPeriod;
readonly class ReopenPayrollPeriodCommand
{
    public function __construct(public string $periodId, public string $reopenedBy) {}
}
