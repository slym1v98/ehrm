<?php

namespace App\Modules\Leave\Domain\Repositories;

use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveType;
use App\Modules\Leave\Domain\Aggregates\LeaveType\LeaveTypeId;

interface LeaveTypeRepositoryInterface
{
    public function findById(LeaveTypeId $id): ?LeaveType;
    public function findByCode(string $code): ?LeaveType;
    /** @return LeaveType[] */
    public function all(): array;
    public function save(LeaveType $type): void;
}
