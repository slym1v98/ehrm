<?php

namespace App\Modules\Employee\Application\QueryHandlers;

use App\Modules\Employee\Application\Queries\GetEmployeeContractsQuery;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Repositories\ContractRepositoryInterface;

class GetEmployeeContractsHandler
{
    public function __construct(private ContractRepositoryInterface $contracts) {}

    public function handle(GetEmployeeContractsQuery $query): array
    {
        return $this->contracts->findAllPaginated($query->page, $query->perPage, EmployeeId::fromString($query->employeeId));
    }
}
