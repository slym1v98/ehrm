<?php

namespace App\Modules\Payroll\Application\CommandHandlers\PayrollRun;

use App\Modules\Payroll\Application\Commands\PayrollRun\StartPayrollRunCommand;
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Aggregates\PayrollRun\{PayrollRun, PayrollRunId};
use App\Modules\Payroll\Domain\Repositories\{PayrollPeriodRepositoryInterface, PayrollRunRepositoryInterface};
use App\Modules\Payroll\Domain\Exceptions\{PayrollPeriodNotFoundException, DuplicatePayrollRunException};
use App\Modules\Payroll\Domain\Ports\EmployeeContractReadPort;

readonly class StartPayrollRunHandler
{
    public function __construct(
        private PayrollPeriodRepositoryInterface $periodRepo,
        private PayrollRunRepositoryInterface $runRepo,
        private EmployeeContractReadPort $contractPort,
    ) {}

    public function handle(StartPayrollRunCommand $command): PayrollRun
    {
        $periodId = PayrollPeriodId::fromString($command->periodId);
        $period = $this->periodRepo->findById($periodId);
        if ($period === null) throw PayrollPeriodNotFoundException::default();

        // Check no running run
        if ($this->runRepo->hasRunningRun($periodId)) {
            throw DuplicatePayrollRunException::default();
        }

        // Transition period to calculating
        $event = $period->startRun($command->triggeredBy);
        $this->periodRepo->save($period);

        // Create run
        $run = PayrollRun::start(
            PayrollRunId::generate(),
            $periodId,
            'initial',
            '1.0',
            $command->triggeredBy,
        );
        $this->runRepo->save($run);

        return $run;
    }
}
