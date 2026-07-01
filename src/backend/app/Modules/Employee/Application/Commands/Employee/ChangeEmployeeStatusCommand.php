<?php

namespace App\Modules\Employee\Application\Commands\Employee;

final readonly class ChangeEmployeeStatusCommand
{
    public function __construct(public string $employeeId, public string $status, public ?string $reason = null) {}
}
