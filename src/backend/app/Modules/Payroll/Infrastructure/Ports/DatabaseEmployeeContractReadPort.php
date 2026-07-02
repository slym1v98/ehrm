<?php

namespace App\Modules\Payroll\Infrastructure\Ports;

use App\Modules\Payroll\Domain\Ports\EmployeeContractReadPort;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;

class DatabaseEmployeeContractReadPort implements EmployeeContractReadPort
{
    public function getContractForEmployee(string $employeeId, DateTimeImmutable $asOf): ?array
    {
        $row = DB::table('employee_contracts')
            ->where('employee_id', $employeeId)
            ->where('start_date', '<=', $asOf->format('Y-m-d'))
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$row) return null;

        return [
            'employee_id' => $row->employee_id,
            'base_salary' => (float) ($row->base_salary ?? 0),
            'effective_date' => $row->start_date ?? '',
            'position_id' => $row->position_id ?? null,
        ];
    }

    public function getActiveEmployeeIds(DateTimeImmutable $asOf): array
    {
        return DB::table('employees')
            ->where('status', 'active')
            ->pluck('id')
            ->map(fn($v) => (string)$v)
            ->all();
    }
}
