<?php

namespace App\Modules\Employee\Domain\Events;

use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use DateTimeImmutable;

final readonly class EmployeeCreated
{
    public function __construct(public EmployeeId $employeeId, public string $employeeCode, public string $fullName, public string $status, public DateTimeImmutable $occurredAt) {}
}
