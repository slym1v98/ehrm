<?php

namespace App\Modules\Employee\Application\CommandHandlers\Contract;

use App\Modules\Employee\Application\Commands\Contract\TerminateContractCommand;
use App\Modules\Employee\Domain\Aggregates\Contract\ContractId;
use App\Modules\Employee\Domain\Exceptions\ContractNotFoundException;
use App\Modules\Employee\Domain\Repositories\ContractRepositoryInterface;
use App\Modules\Identity\Application\Services\AuthorizationService;

class TerminateContractHandler
{
    public function __construct(private ContractRepositoryInterface $contracts, private AuthorizationService $authorizationService) {}

    public function handle(TerminateContractCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'employee.contract.terminate');
        $contract = $this->contracts->findById(ContractId::fromString($command->contractId));
        if (! $contract) throw new ContractNotFoundException($command->contractId);
        $contract->terminate();
        $this->contracts->saveAndDispatch($contract);
    }
}
