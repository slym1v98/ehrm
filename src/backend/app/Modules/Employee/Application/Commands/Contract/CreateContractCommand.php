<?php

namespace App\Modules\Employee\Application\Commands\Contract;

final readonly class CreateContractCommand
{
    public function __construct(public string $employeeId, public string $contractType, public string $startDate, public ?string $endDate = null, public ?float $baseSalary = null) {}
}
