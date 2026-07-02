<?php

namespace App\Modules\Attendance\Application\QueryHandlers;

use App\Modules\Attendance\Application\Queries\ListAttendancePeriodsQuery;
use App\Modules\Attendance\Domain\Repositories\AttendancePeriodRepositoryInterface;

class ListAttendancePeriodsHandler
{
    public function __construct(private AttendancePeriodRepositoryInterface $repo) {}
    public function handle(ListAttendancePeriodsQuery $query): array { return $this->repo->findPaginated($query->perPage, $query->page); }
}
