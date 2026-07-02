<?php

namespace App\Modules\Attendance\Application\QueryHandlers;

use App\Modules\Attendance\Application\Queries\GetEmployeeAttendanceQuery;
use App\Modules\Attendance\Domain\Repositories\AttendanceTimesheetRepositoryInterface;

class GetEmployeeAttendanceHandler
{
    public function __construct(private AttendanceTimesheetRepositoryInterface $repo) {}
    public function handle(GetEmployeeAttendanceQuery $query): array { return $this->repo->findByEmployeeAndRange($query->employeeId, $query->from, $query->to); }
}
