<?php

namespace App\Modules\Employee\Application\CommandHandlers\Contract;

use App\Modules\Employee\Application\Commands\Contract\RenewContractCommand;
use App\Modules\Employee\Domain\Aggregates\Contract\ContractId;
use App\Modules\Employee\Domain\Aggregates\Contract\ContractTerm;
use App\Modules\Employee\Domain\Aggregates\Contract\DateRange;
use App\Modules\Employee\Domain\Exceptions\ContractNotFoundException;
use App\Modules\Employee\Domain\Repositories\ContractRepositoryInterface;
use App\Modules\Identity\Application\Services\AuthorizationService;

class RenewContractHandler
{
    public function __construct(private ContractRepositoryInterface $contracts, private AuthorizationService $authorizationService) {}

    public function handle(RenewContractCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'employee.contract.renew');
        $contract = $this->contracts->findById(ContractId::fromString($command->contractId));
        if (! $contract) throw new ContractNotFoundException($command->contractId);

        $renewed = $contract->renew(
            ContractId::generate(),
            'CT' . now()->format('YmdHis'),
            new ContractTerm(
                $contract->contractType(),
                new DateRange(
                    new \DateTimeImmutable($command->startDate),
                    $command->endDate ? new \DateTimeImmutable($command->endDate) : null,
                ),
                $command->baseSalary,
            ),
        );

        $this->contracts->saveAndDispatch($contract);
        $this->contracts->saveAndDispatch($renewed);
    }
}
