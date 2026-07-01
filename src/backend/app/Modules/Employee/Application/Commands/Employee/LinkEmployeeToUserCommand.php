<?php

namespace App\Modules\Employee\Application\Commands\Employee;

final readonly class LinkEmployeeToUserCommand
{
    public function __construct(public string $employeeId, public string $userId) {}
}
