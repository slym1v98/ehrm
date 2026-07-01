<?php

namespace App\Modules\Employee\Application\QueryHandlers;

use App\Modules\Employee\Application\Queries\GetEmployeeDocumentsQuery;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Repositories\EmployeeDocumentRepositoryInterface;

class GetEmployeeDocumentsHandler
{
    public function __construct(private EmployeeDocumentRepositoryInterface $documents) {}

    public function handle(GetEmployeeDocumentsQuery $query): array
    {
        return $this->documents->findAllPaginated($query->page, $query->perPage, EmployeeId::fromString($query->employeeId));
    }
}
