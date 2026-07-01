<?php

namespace App\Modules\Shift\Application\Queries;

final readonly class GetDepartmentShiftsQuery
{
    public function __construct(public string $departmentId, public string $date) {}
}
