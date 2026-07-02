<?php

namespace App\Modules\Payroll\Application\CommandHandlers\PayrollPeriod;

use App\Modules\Payroll\Application\Commands\PayrollPeriod\ReopenPayrollPeriodCommand;
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Repositories\PayrollPeriodRepositoryInterface;
use App\Modules\Payroll\Domain\Exceptions\PayrollPeriodNotFoundException;

readonly class ReopenPayrollPeriodHandler
{
    public function __construct(private PayrollPeriodRepositoryInterface $periodRepo) {}

    public function handle(ReopenPayrollPeriodCommand $command): void
    {
        $id = PayrollPeriodId::fromString($command->periodId);
        $period = $this->periodRepo->findById($id);
        if ($period === null) throw PayrollPeriodNotFoundException::default();

        $period->reopen($command->reopenedBy);
        $this->periodRepo->save($period);
    }
}
