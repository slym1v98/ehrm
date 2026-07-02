<?php

namespace App\Modules\Leave\Domain\Repositories;

use App\Modules\Leave\Domain\Aggregates\LeaveBalance\LeaveBalance;
use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;

interface LeaveBalanceRepositoryInterface
{
    public function findByEmployeeTypeYear(string $employeeId, LeaveTypeId $typeId, int $year): ?LeaveBalance;
    /** @return LeaveBalance[] */
    public function findByEmployee(string $employeeId, ?int $year = null): array;
    public function save(LeaveBalance $balance): void;
}
