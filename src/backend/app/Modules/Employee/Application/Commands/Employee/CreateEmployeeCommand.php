<?php

namespace App\Modules\Employee\Application\Commands\Employee;

final readonly class CreateEmployeeCommand
{
    public function __construct(public string $firstName, public string $lastName) {}
}
