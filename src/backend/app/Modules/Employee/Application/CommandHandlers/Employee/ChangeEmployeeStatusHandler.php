<?php

namespace App\Modules\Employee\Application\CommandHandlers\Employee;

use App\Modules\Employee\Application\Commands\Employee\ChangeEmployeeStatusCommand;
use App\Modules\Employee\Application\Services\EmployeeLifecyclePolicy;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeStatus;
use App\Modules\Employee\Domain\Exceptions\EmployeeNotFoundException;
use App\Modules\Employee\Domain\Repositories\EmployeeRepositoryInterface;
use App\Modules\Identity\Application\Services\AuthorizationService;

class ChangeEmployeeStatusHandler
{
    public function __construct(private EmployeeRepositoryInterface $employees, private EmployeeLifecyclePolicy $policy, private AuthorizationService $authorizationService) {}

    public function handle(ChangeEmployeeStatusCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'employee.status.change');
        $employee = $this->employees->findById(EmployeeId::fromString($command->employeeId));
        if (! $employee) throw new EmployeeNotFoundException($command->employeeId);
        $employee->changeStatus(EmployeeStatus::from($command->status), $this->policy, $command->reason);
        $this->employees->saveAndDispatch($employee);
    }
}
