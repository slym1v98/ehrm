<?php

namespace App\Modules\Payroll\Application\Queries;

use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Repositories\PayslipRepositoryInterface;

readonly class PayslipListQuery
{
    public function __construct(private PayslipRepositoryInterface $repo) {}

    public function getByEmployee(string $employeeId): array
    {
        return $this->repo->findByEmployee($employeeId);
    }

    public function getByPeriod(string $periodId): array
    {
        return $this->repo->findByPeriod(PayrollPeriodId::fromString($periodId));
    }
}
