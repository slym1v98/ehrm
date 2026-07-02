<?php

namespace App\Modules\Attendance\Application\QueryHandlers;

use App\Modules\Attendance\Application\Queries\ListPendingAdjustmentsQuery;
use App\Modules\Attendance\Domain\Repositories\AttendanceAdjustmentRequestRepositoryInterface;

class ListPendingAdjustmentsHandler
{
    public function __construct(private AttendanceAdjustmentRequestRepositoryInterface $repo) {}
    public function handle(ListPendingAdjustmentsQuery $query): array { return $this->repo->findPendingPaginated($query->perPage, $query->page); }
}
