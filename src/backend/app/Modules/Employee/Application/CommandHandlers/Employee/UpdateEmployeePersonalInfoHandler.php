<?php

namespace App\Modules\Employee\Application\CommandHandlers\Employee;

use App\Modules\Employee\Application\Commands\Employee\UpdateEmployeePersonalInfoCommand;
use App\Modules\Employee\Domain\Aggregates\Employee\Address;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Aggregates\Employee\PersonalName;
use App\Modules\Employee\Domain\Exceptions\EmployeeNotFoundException;
use App\Modules\Employee\Domain\Repositories\EmployeeRepositoryInterface;
use App\Modules\Identity\Application\Services\AuthorizationService;

class UpdateEmployeePersonalInfoHandler
{
    public function __construct(private EmployeeRepositoryInterface $employees, private AuthorizationService $authorizationService) {}

    public function handle(UpdateEmployeePersonalInfoCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'employee.update');
        $employee = $this->employees->findById(EmployeeId::fromString($command->employeeId));
        if (! $employee) throw new EmployeeNotFoundException($command->employeeId);
        $employee->updatePersonalInfo(
            PersonalName::of($command->firstName, $command->lastName),
            $command->dob ? new \DateTimeImmutable($command->dob) : null,
            $command->gender,
            $command->personalEmail,
            $command->phone,
            new Address(),
        );
        $this->employees->save($employee);
    }
}
