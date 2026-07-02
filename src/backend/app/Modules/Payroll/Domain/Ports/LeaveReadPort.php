<?php

namespace App\Modules\Payroll\Domain\Ports;

use DateTimeImmutable;

interface LeaveReadPort
{
    /** @return array{paid_days:float, unpaid_days:float} */
    public function getLeaveForEmployee(string $employeeId, DateTimeImmutable $start, DateTimeImmutable $end): array;
}
