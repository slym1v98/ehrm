<?php

namespace App\Modules\Payroll\Domain\Events;

readonly class PayrollApproved
{
    public function __construct(public string $periodId, public string $approvedBy) {}
}
