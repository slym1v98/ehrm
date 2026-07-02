<?php

namespace App\Modules\Attendance\Application\Queries;

final readonly class ListPendingAdjustmentsQuery
{
    public function __construct(public int $perPage = 15, public int $page = 1) {}
}
