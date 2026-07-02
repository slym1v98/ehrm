<?php

namespace App\Modules\Payroll\Domain\ValueObjects;

readonly class EntryError
{
    public function __construct(
        public string $employeeId,
        public string $message,
    ) {}
}
