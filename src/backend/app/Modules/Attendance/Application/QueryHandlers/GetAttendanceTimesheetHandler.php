<?php

namespace App\Modules\Attendance\Application\QueryHandlers;

use App\Modules\Attendance\Application\Queries\GetAttendanceTimesheetQuery;
use App\Modules\Attendance\Domain\Repositories\AttendanceTimesheetRepositoryInterface;

class GetAttendanceTimesheetHandler
{
    public function __construct(private AttendanceTimesheetRepositoryInterface $repo) {}
    public function handle(GetAttendanceTimesheetQuery $query): array { return $this->repo->findByEmployeeAndRange($query->employeeId, $query->from, $query->to); }
}
