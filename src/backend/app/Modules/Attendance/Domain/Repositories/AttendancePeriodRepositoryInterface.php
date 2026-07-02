<?php

namespace App\Modules\Attendance\Domain\Repositories;

use App\Modules\Attendance\Domain\Aggregates\AttendancePeriod\AttendancePeriod;
use App\Modules\Attendance\Domain\Aggregates\AttendancePeriod\AttendancePeriodId;

interface AttendancePeriodRepositoryInterface
{
    public function findById(string $id): ?AttendancePeriod;
    public function findByCode(string $code): ?AttendancePeriod;
    public function findClosedByDate(string $date): ?AttendancePeriod;
    public function saveAndDispatch(AttendancePeriod $period): void;
    public function findPaginated(int $perPage = 15, int $page = 1): array;
}
