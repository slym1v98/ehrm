<?php

namespace App\Modules\Employee\Domain\Events;

use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use DateTimeImmutable;

final readonly class EmployeePersonalInfoUpdated
{
    public function __construct(public EmployeeId $employeeId, public array $changedFields, public DateTimeImmutable $occurredAt) {}
}
