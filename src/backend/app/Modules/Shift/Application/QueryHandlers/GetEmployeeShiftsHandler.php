<?php

namespace App\Modules\Shift\Application\QueryHandlers;

use App\Modules\Shift\Application\Queries\GetEmployeeShiftsQuery;
use App\Modules\Shift\Domain\Repositories\ShiftAssignmentRepositoryInterface;
use DateTimeImmutable;

class GetEmployeeShiftsHandler
{
    public function __construct(private ShiftAssignmentRepositoryInterface $assignments) {}

    public function handle(GetEmployeeShiftsQuery $query): array
    {
        return $this->assignments->findActiveByEntity('employee', $query->employeeId, new DateTimeImmutable($query->date));
    }
}
