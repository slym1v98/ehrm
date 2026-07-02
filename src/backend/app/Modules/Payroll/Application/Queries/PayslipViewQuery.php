<?php

namespace App\Modules\Payroll\Application\Queries;

use App\Modules\Payroll\Domain\Aggregates\Payslip\{Payslip, PayslipId};
use App\Modules\Payroll\Domain\Repositories\PayslipRepositoryInterface;

readonly class PayslipViewQuery
{
    public function __construct(private PayslipRepositoryInterface $repo) {}

    public function getById(string $payslipId): ?Payslip
    {
        return $this->repo->findById(PayslipId::fromString($payslipId));
    }
}
