<?php

namespace App\Modules\Employee\Domain\Events;

use App\Modules\Employee\Domain\Aggregates\Contract\ContractId;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use DateTimeImmutable;

final readonly class ContractCreated
{
    public function __construct(public ContractId $contractId, public EmployeeId $employeeId, public string $contractType, public string $contractNumber, public DateTimeImmutable $occurredAt) {}
}
