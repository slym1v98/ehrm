<?php

namespace App\Modules\Employee\Domain\Repositories;

use App\Modules\Employee\Domain\Aggregates\Employee\Employee;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;

interface EmployeeRepositoryInterface
{
    public function findById(EmployeeId $id): ?Employee;
    public function findByCode(string $code): ?Employee;
    public function findByUserId(string $userId): ?Employee;
    public function findAllPaginated(int $page, int $perPage = 15): array;
    public function existsByCode(string $code): bool;
    public function save(Employee $employee): void;
}
