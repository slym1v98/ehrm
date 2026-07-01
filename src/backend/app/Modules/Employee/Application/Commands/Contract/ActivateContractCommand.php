<?php

namespace App\Modules\Employee\Application\Commands\Contract;

final readonly class ActivateContractCommand
{
    public function __construct(public string $contractId) {}
}
