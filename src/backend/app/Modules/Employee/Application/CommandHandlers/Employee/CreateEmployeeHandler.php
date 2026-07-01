<?php

namespace App\Modules\Employee\Application\CommandHandlers\Employee;

use App\Modules\Employee\Application\Commands\Employee\CreateEmployeeCommand;
use App\Modules\Employee\Application\Services\EmployeeCodeGenerator;
use App\Modules\Employee\Domain\Aggregates\Employee\Employee;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeCode;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Aggregates\Employee\PersonalName;
use App\Modules\Employee\Domain\Exceptions\EmployeeCodeAlreadyExistsException;
use App\Modules\Employee\Domain\Repositories\EmployeeRepositoryInterface;
use App\Modules\Identity\Application\Services\AuthorizationService;

class CreateEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private EmployeeCodeGenerator $codeGenerator,
        private AuthorizationService $authorizationService,
    ) {}

    public function handle(CreateEmployeeCommand $command, string $userId): Employee
    {
        $this->authorizationService->requirePermission($userId, 'employee.create');

        $code = $this->codeGenerator->generate();
        if ($this->employees->existsByCode($code)) {
            throw new EmployeeCodeAlreadyExistsException($code);
        }

        $employee = Employee::create(EmployeeId::generate(), EmployeeCode::fromString($code), PersonalName::of($command->firstName, $command->lastName));
        $this->employees->saveAndDispatch($employee);
        return $employee;
    }
}
