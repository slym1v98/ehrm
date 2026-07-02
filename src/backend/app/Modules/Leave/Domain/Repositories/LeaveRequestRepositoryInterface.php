<?php

namespace App\Modules\Leave\Domain\Repositories;

use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequest;
use App\Modules\Leave\Domain\Aggregates\LeaveRequest\LeaveRequestId;
use Carbon\CarbonImmutable;

interface LeaveRequestRepositoryInterface
{
    public function findById(LeaveRequestId $id): ?LeaveRequest;
    /** @return LeaveRequest[] */
    public function findOverlapping(string $employeeId, CarbonImmutable $start, CarbonImmutable $end, ?LeaveRequestId $excludeId = null): array;
    /** @return mixed */
    public function findByEmployee(?string $employeeId, array $filters = [], int $perPage = 15): mixed;
    public function save(LeaveRequest $request): void;
}
