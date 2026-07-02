<?php

namespace App\Modules\Leave\Domain\Repositories;

use App\Modules\Leave\Domain\Aggregates\LeavePolicy\LeavePolicy;
use App\Modules\Leave\Domain\Aggregates\LeavePolicy\LeavePolicyId;
use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;
use Carbon\CarbonImmutable;

interface LeavePolicyRepositoryInterface
{
    public function findById(LeavePolicyId $id): ?LeavePolicy;
    public function findByType(LeaveTypeId $typeId, CarbonImmutable $date): ?LeavePolicy;
    /** @return LeavePolicy[] */
    public function all(): array;
    public function save(LeavePolicy $policy): void;
}
