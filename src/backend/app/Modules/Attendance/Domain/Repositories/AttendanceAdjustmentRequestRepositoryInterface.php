<?php

namespace App\Modules\Attendance\Domain\Repositories;

use App\Modules\Attendance\Domain\Aggregates\AttendanceAdjustmentRequest\AttendanceAdjustmentRequest;
use App\Modules\Attendance\Domain\Aggregates\AttendanceAdjustmentRequest\AttendanceAdjustmentRequestId;

interface AttendanceAdjustmentRequestRepositoryInterface
{
    public function findById(string $id): ?AttendanceAdjustmentRequest;
    public function hasPendingForTimesheet(string $timesheetId): bool;
    public function saveAndDispatch(AttendanceAdjustmentRequest $request): void;
    public function findPendingPaginated(int $perPage = 15, int $page = 1): array;
}
