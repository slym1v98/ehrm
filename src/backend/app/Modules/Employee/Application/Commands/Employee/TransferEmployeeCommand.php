<?php

namespace App\Modules\Employee\Application\Commands\Employee;

final readonly class TransferEmployeeCommand
{
    public function __construct(public string $employeeId, public ?string $branchId, public ?string $departmentId, public ?string $positionId) {}
}
