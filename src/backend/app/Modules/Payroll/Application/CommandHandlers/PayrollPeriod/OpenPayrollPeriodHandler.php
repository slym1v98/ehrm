<?php

namespace App\Modules\Payroll\Application\CommandHandlers\PayrollPeriod;

use App\Modules\Payroll\Application\Commands\PayrollPeriod\OpenPayrollPeriodCommand;
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\{PayrollPeriod, PayrollPeriodId};
use App\Modules\Payroll\Domain\Repositories\PayrollPeriodRepositoryInterface;

readonly class OpenPayrollPeriodHandler
{
    public function __construct(
        private PayrollPeriodRepositoryInterface $periodRepo,
    ) {}

    public function handle(OpenPayrollPeriodCommand $command): PayrollPeriod
    {
        // Check unique period_code
        $existing = $this->periodRepo->findByCode($command->periodCode);
        if ($existing !== null) {
            throw new \InvalidArgumentException("Period code '{$command->periodCode}' already exists.");
        }

        $period = PayrollPeriod::open(
            PayrollPeriodId::generate(),
            $command->periodCode,
            $command->startDate,
            $command->endDate,
            $command->cutoffDate,
            $command->attendancePeriodId,
            $command->openedBy,
        );

        $this->periodRepo->save($period);
        return $period;
    }
}
