<?php
namespace App\Modules\Payroll\Application\Commands\PayrollRun;
readonly class StartPayrollRunCommand
{
    public function __construct(public string $periodId, public string $triggeredBy) {}
}
