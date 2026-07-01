<?php

namespace App\Modules\Employee\Application\Commands\Employee;

final readonly class UpdateEmployeePersonalInfoCommand
{
    public function __construct(public string $employeeId, public string $firstName, public string $lastName, public ?string $dob = null, public ?string $gender = null, public ?string $personalEmail = null, public ?string $phone = null) {}
}
