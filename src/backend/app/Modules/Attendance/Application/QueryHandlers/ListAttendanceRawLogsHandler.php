<?php

namespace App\Modules\Attendance\Application\QueryHandlers;

use App\Modules\Attendance\Application\Queries\ListAttendanceRawLogsQuery;
use App\Modules\Attendance\Domain\Repositories\AttendanceRawLogRepositoryInterface;

class ListAttendanceRawLogsHandler
{
    public function __construct(private AttendanceRawLogRepositoryInterface $repo) {}
    public function handle(ListAttendanceRawLogsQuery $query): array { return $this->repo->findPaginated($query->perPage, $query->page); }
}
