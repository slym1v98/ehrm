<?php

namespace App\Modules\Employee\Application\Queries;

final readonly class GetEmployeeQuery
{
    public function __construct(public string $employeeId) {}
}
