<?php

namespace App\Modules\Employee\Application\Commands\Contract;

final readonly class RenewContractCommand
{
    public function __construct(public string $contractId, public string $startDate, public ?string $endDate = null, public ?float $baseSalary = null) {}
}
