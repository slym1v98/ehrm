<?php

namespace App\Modules\Employee\Application\Services;

final class EmployeeCodeGenerator
{
    public function generate(): string
    {
        return 'EMP' . now()->format('YmdHis');
    }
}
