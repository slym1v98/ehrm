<?php

namespace App\Modules\Employee\Domain\Events;

use App\Modules\Employee\Domain\Aggregates\Contract\ContractId;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use DateTimeImmutable;

final readonly class ContractActivated
{
    public function __construct(public ContractId $contractId, public EmployeeId $employeeId, public DateTimeImmutable $occurredAt) {}
}
