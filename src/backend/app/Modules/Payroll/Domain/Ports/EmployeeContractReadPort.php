<?php

namespace App\Modules\Payroll\Domain\Ports;

use DateTimeImmutable;

interface EmployeeContractReadPort
{
    /** @return array{employee_id:string, base_salary:float, effective_date:string, position_id:?string}|null */
    public function getContractForEmployee(string $employeeId, DateTimeImmutable $asOf): ?array;

    /** @return string[] Active employee IDs at $asOf date */
    public function getActiveEmployeeIds(DateTimeImmutable $asOf): array;
}
