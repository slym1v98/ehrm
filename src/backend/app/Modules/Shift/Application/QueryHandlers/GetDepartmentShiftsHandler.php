<?php

namespace App\Modules\Shift\Application\QueryHandlers;

use App\Modules\Shift\Application\Queries\GetDepartmentShiftsQuery;
use App\Modules\Shift\Domain\Repositories\ShiftAssignmentRepositoryInterface;
use DateTimeImmutable;

class GetDepartmentShiftsHandler
{
    public function __construct(private ShiftAssignmentRepositoryInterface $assignments) {}

    public function handle(GetDepartmentShiftsQuery $query): array
    {
        return $this->assignments->findActiveByEntity('department', $query->departmentId, new DateTimeImmutable($query->date));
    }
}
