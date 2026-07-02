<?php

namespace App\Modules\Payroll\Application\Queries;

use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\Repositories\PayrollEntryRepositoryInterface;

readonly class PayrollEntryListQuery
{
    public function __construct(private PayrollEntryRepositoryInterface $repo) {}

    public function getByPeriod(string $periodId): array
    {
        return $this->repo->findByPeriod(PayrollPeriodId::fromString($periodId));
    }
}
