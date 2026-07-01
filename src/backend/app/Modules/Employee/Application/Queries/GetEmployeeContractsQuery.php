<?php

namespace App\Modules\Employee\Application\Queries;

final readonly class GetEmployeeContractsQuery
{
    public function __construct(public string $employeeId, public int $page = 1, public int $perPage = 15) {}
}
