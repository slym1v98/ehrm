<?php
namespace App\Modules\Payroll\Application\Commands\PayrollRun;
readonly class CompletePayrollRunCommand
{
    public function __construct(public string $runId, public string $periodId) {}
}
