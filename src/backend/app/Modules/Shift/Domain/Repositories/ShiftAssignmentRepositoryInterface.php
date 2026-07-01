<?php

namespace App\Modules\Shift\Domain\Repositories;

use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignment;
use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignmentId;
use DateTimeImmutable;

interface ShiftAssignmentRepositoryInterface
{
    public function findById(ShiftAssignmentId $id): ?ShiftAssignment;
    /** @return ShiftAssignment[] */
    public function findByEmployeeId(string $employeeId): array;
    /** @return ShiftAssignment[] */
    public function findByDepartmentId(string $departmentId): array;
    /** @return ShiftAssignment[] */
    public function findActiveByEntity(string $entityType, string $entityId, DateTimeImmutable $date): array;
    /** @return ShiftAssignment[] */
    public function findAllPaginated(int $page, int $perPage = 15): array;
    public function saveAndDispatch(ShiftAssignment $assignment): void;
}
