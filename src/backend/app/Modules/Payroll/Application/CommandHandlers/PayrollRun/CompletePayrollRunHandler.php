<?php

namespace App\Modules\Payroll\Application\CommandHandlers\PayrollRun;

use App\Modules\Payroll\Application\Commands\PayrollRun\CompletePayrollRunCommand;
use App\Modules\Payroll\Domain\Aggregates\PayrollRun\PayrollRunId;
use App\Modules\Payroll\Domain\Repositories\{PayrollRunRepositoryInterface, PayrollPeriodRepositoryInterface};
use App\Modules\Payroll\Domain\Exceptions\{PayrollRunNotFoundException, PayrollPeriodNotFoundException};
use App\Modules\Payroll\Domain\Services\PayrollCalculator;
use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntry;
use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntryId;
use App\Modules\Payroll\Domain\Repositories\PayrollEntryRepositoryInterface;
use App\Modules\Payroll\Domain\Repositories\PayrollComponentRepositoryInterface;
use App\Modules\Payroll\Domain\Ports\EmployeeContractReadPort;

readonly class CompletePayrollRunHandler
{
    public function __construct(
        private PayrollRunRepositoryInterface $runRepo,
        private PayrollPeriodRepositoryInterface $periodRepo,
        private PayrollEntryRepositoryInterface $entryRepo,
        private PayrollComponentRepositoryInterface $componentRepo,
        private PayrollCalculator $calculator,
        private EmployeeContractReadPort $contractPort,
    ) {}

    public function handle(CompletePayrollRunCommand $command): void
    {
        $runId = PayrollRunId::fromString($command->runId);
        $run = $this->runRepo->findById($runId);
        if ($run === null) throw PayrollRunNotFoundException::default();

        $periodId = $run->getPeriodId();
        $period = $this->periodRepo->findById($periodId);
        if ($period === null) throw PayrollPeriodNotFoundException::default();

        // Get active employees and components
        $employeeIds = $this->contractPort->getActiveEmployeeIds($period->getEndDate());
        $components = $this->componentRepo->findActive();

        // Calculate entries
        $entries = $this->calculator->calculateForPeriod(
            $employeeIds,
            $components,
            $periodId,
            $runId,
            $period->getStartDate(),
            $period->getEndDate(),
        );

        // Persist entries
        $errors = 0;
        foreach ($entries as $entry) {
            $this->entryRepo->save($entry);
            if ($entry->getStatus() === 'error') $errors++;
        }

        // Complete run
        $run->complete($errors > 0 ? "$errors entries had errors" : null);
        $this->runRepo->save($run);

        // Transition period to completed
        $period->completeRun();
        $this->periodRepo->save($period);
    }
}
