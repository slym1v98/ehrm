<?php

namespace App\Modules\Shift\Application\Queries;

final readonly class GetEmployeeShiftsQuery
{
    public function __construct(public string $employeeId, public string $date) {}
}
