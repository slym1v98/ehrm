<?php

namespace App\Modules\Employee\Application\QueryHandlers;

use App\Modules\Employee\Application\Queries\GetEmployeeQuery;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Exceptions\EmployeeNotFoundException;
use App\Modules\Employee\Domain\Repositories\EmployeeRepositoryInterface;

class GetEmployeeHandler
{
    public function __construct(private EmployeeRepositoryInterface $employees) {}

    public function handle(GetEmployeeQuery $query): mixed
    {
        $employee = $this->employees->findById(EmployeeId::fromString($query->employeeId));
        if (! $employee) throw new EmployeeNotFoundException($query->employeeId);
        return $employee;
    }
}
