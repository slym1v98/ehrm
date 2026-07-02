<?php

namespace App\Modules\Attendance\Application\Queries;

final readonly class ListAttendanceRawLogsQuery
{
    public function __construct(public int $perPage = 15, public int $page = 1) {}
}
