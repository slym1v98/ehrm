<?php

namespace App\Modules\Payroll\Application\Queries;

use App\Modules\Payroll\Domain\Repositories\PayrollPeriodRepositoryInterface;

readonly class PayrollPeriodListQuery
{
    public function __construct(private PayrollPeriodRepositoryInterface $repo) {}

    /** @return \App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriod[] */
    public function getAll(array $filters = []): array
    {
        return $this->repo->findAll($filters);
    }
}
