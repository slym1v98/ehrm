<?php

namespace App\Modules\Employee\Application\Commands\Contract;

final readonly class TerminateContractCommand
{
    public function __construct(public string $contractId) {}
}
