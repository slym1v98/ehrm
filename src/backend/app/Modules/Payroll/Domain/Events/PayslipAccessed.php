<?php

namespace App\Modules\Payroll\Domain\Events;

readonly class PayslipAccessed
{
    public function __construct(public string $payslipId, public string $employeeId, public string $accessedBy) {}
}
