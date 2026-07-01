<?php

namespace App\Modules\Employee\Domain\Events;

use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use DateTimeImmutable;

final readonly class EmployeeManagerChanged
{
    public function __construct(public EmployeeId $employeeId, public ?EmployeeId $oldManagerId, public ?EmployeeId $newManagerId, public DateTimeImmutable $occurredAt) {}
}
