<?php

namespace App\Modules\Employee\Application\CommandHandlers\Contract;

use App\Modules\Employee\Application\Commands\Contract\CreateContractCommand;
use App\Modules\Employee\Domain\Aggregates\Contract\Contract;
use App\Modules\Employee\Domain\Aggregates\Contract\ContractId;
use App\Modules\Employee\Domain\Aggregates\Contract\ContractTerm;
use App\Modules\Employee\Domain\Aggregates\Contract\DateRange;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Exceptions\EmployeeNotFoundException;
use App\Modules\Employee\Domain\Repositories\ContractRepositoryInterface;
use App\Modules\Employee\Domain\Repositories\EmployeeRepositoryInterface;
use App\Modules\Identity\Application\Services\AuthorizationService;

class CreateContractHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private ContractRepositoryInterface $contracts,
        private AuthorizationService $authorizationService,
    ) {}

    public function handle(CreateContractCommand $command, string $userId): Contract
    {
        $this->authorizationService->requirePermission($userId, 'employee.contract.create');
        $employee = $this->employees->findById(EmployeeId::fromString($command->employeeId));
        if (! $employee) throw new EmployeeNotFoundException($command->employeeId);

        $contract = Contract::create(
            ContractId::generate(),
            EmployeeId::fromString($command->employeeId),
            'CT' . now()->format('YmdHis'),
            new ContractTerm(
                $command->contractType,
                new DateRange(
                    new \DateTimeImmutable($command->startDate),
                    $command->endDate ? new \DateTimeImmutable($command->endDate) : null,
                ),
                $command->baseSalary,
            ),
        );

        $this->contracts->saveAndDispatch($contract);
        return $contract;
    }
}
