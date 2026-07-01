<?php

namespace App\Modules\Employee\Application\QueryHandlers;

use App\Modules\Employee\Application\Queries\ListEmployeesQuery;
use App\Modules\Employee\Domain\Repositories\EmployeeRepositoryInterface;

class ListEmployeesHandler
{
    public function __construct(private EmployeeRepositoryInterface $employees) {}

    public function handle(ListEmployeesQuery $query): array
    {
        return $this->employees->findAllPaginated($query->page, $query->perPage);
    }
}
