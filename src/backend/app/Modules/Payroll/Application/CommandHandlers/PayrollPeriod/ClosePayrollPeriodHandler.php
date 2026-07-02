<?php

namespace App\Modules\Payroll\Application\CommandHandlers\PayrollPeriod;

use App\Modules\Payroll\Application\Commands\PayrollPeriod\ClosePayrollPeriodCommand;
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Repositories\PayrollPeriodRepositoryInterface;
use App\Modules\Payroll\Domain\Exceptions\PayrollPeriodNotFoundException;

readonly class ClosePayrollPeriodHandler
{
    public function __construct(private PayrollPeriodRepositoryInterface $periodRepo) {}

    public function handle(ClosePayrollPeriodCommand $command): void
    {
        $id = PayrollPeriodId::fromString($command->periodId);
        $period = $this->periodRepo->findById($id);
        if ($period === null) throw PayrollPeriodNotFoundException::default();

        $period->lock($command->closedBy);
        $this->periodRepo->save($period);
    }
}
