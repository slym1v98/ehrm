<?php

namespace App\Modules\Employee\Domain\Events;

use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use DateTimeImmutable;

final readonly class EmployeeStatusChanged
{
    public function __construct(public EmployeeId $employeeId, public string $oldStatus, public string $newStatus, public ?string $reason, public DateTimeImmutable $occurredAt) {}
}
