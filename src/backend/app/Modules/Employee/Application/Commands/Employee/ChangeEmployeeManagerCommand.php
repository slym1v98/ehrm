<?php

namespace App\Modules\Employee\Application\Commands\Employee;

final readonly class ChangeEmployeeManagerCommand
{
    public function __construct(public string $employeeId, public ?string $managerId) {}
}
