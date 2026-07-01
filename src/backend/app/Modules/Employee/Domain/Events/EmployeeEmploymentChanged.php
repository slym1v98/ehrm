<?php

namespace App\Modules\Employee\Domain\Events;

use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use DateTimeImmutable;

final readonly class EmployeeEmploymentChanged
{
    public function __construct(public EmployeeId $employeeId, public ?string $branchId, public ?string $departmentId, public ?string $positionId, public DateTimeImmutable $occurredAt) {}
}
