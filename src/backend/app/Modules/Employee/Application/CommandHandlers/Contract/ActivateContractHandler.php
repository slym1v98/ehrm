<?php

namespace App\Modules\Employee\Application\CommandHandlers\Contract;

use App\Modules\Employee\Application\Commands\Contract\ActivateContractCommand;
use App\Modules\Employee\Application\Services\ContractRenewalPolicy;
use App\Modules\Employee\Domain\Aggregates\Contract\ContractId;
use App\Modules\Employee\Domain\Exceptions\ContractNotFoundException;
use App\Modules\Employee\Domain\Exceptions\ContractOverlapException;
use App\Modules\Employee\Domain\Repositories\ContractRepositoryInterface;
use App\Modules\Identity\Application\Services\AuthorizationService;

class ActivateContractHandler
{
    public function __construct(
        private ContractRepositoryInterface $contracts,
        private ContractRenewalPolicy $renewalPolicy,
        private AuthorizationService $authorizationService,
    ) {}

    public function handle(ActivateContractCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'employee.contract.activate');
        $contract = $this->contracts->findById(ContractId::fromString($command->contractId));
        if (! $contract) throw new ContractNotFoundException($command->contractId);

        $activeContracts = $this->contracts->findActiveByEmployeeId($contract->employeeId());
        foreach ($activeContracts as $active) {
            if ($active->status()->value !== 'active') continue;
            if ($this->renewalPolicy->hasOverlap($contract->term(), [$active])) {
                throw new ContractOverlapException($contract->employeeId()->value);
            }
        }

        $contract->activate();
        $this->contracts->saveAndDispatch($contract);
    }
}
