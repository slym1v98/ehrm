<?php

namespace App\Modules\Employee\Application\CommandHandlers\Employee;

use App\Modules\Employee\Application\Commands\Employee\LinkEmployeeToUserCommand;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Exceptions\EmployeeNotFoundException;
use App\Modules\Employee\Domain\Repositories\EmployeeRepositoryInterface;
use App\Modules\Identity\Application\Services\AuthorizationService;

class LinkEmployeeToUserHandler
{
    public function __construct(private EmployeeRepositoryInterface $employees, private AuthorizationService $authorizationService) {}

    public function handle(LinkEmployeeToUserCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'employee.update');
        $employee = $this->employees->findById(EmployeeId::fromString($command->employeeId));
        if (! $employee) throw new EmployeeNotFoundException($command->employeeId);
        $employee->linkUserAccount($command->userId);
        $this->employees->save($employee);
    }
}
