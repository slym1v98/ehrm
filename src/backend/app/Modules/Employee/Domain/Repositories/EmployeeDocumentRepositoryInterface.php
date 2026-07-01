<?php

namespace App\Modules\Employee\Domain\Repositories;

use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\EmployeeDocument;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\EmployeeDocumentId;

interface EmployeeDocumentRepositoryInterface
{
    public function findById(EmployeeDocumentId $id): ?EmployeeDocument;
    /** @return EmployeeDocument[] */
    public function findByEmployeeId(EmployeeId $employeeId): array;
    public function findAllPaginated(int $page, int $perPage = 15, ?EmployeeId $employeeId = null): array;
    public function save(EmployeeDocument $document): void;
}
