<?php

namespace App\Modules\Payroll\Infrastructure\Ports;

use App\Modules\Payroll\Domain\Ports\AttendanceReadPort;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;

class DatabaseAttendanceReadPort implements AttendanceReadPort
{
    public function getAttendanceForEmployee(string $employeeId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $rows = DB::table('attendance_timesheets')
            ->selectRaw('COALESCE(SUM(worked_minutes), 0) as worked_minutes')
            ->selectRaw('COALESCE(SUM(overtime_minutes), 0) as overtime_minutes')
            ->selectRaw('COALESCE(SUM(late_minutes), 0) as late_minutes')
            ->selectRaw('COALESCE(SUM(early_leave_minutes), 0) as early_leave_minutes')
            ->selectRaw('0 as paid_leave_minutes')
            ->selectRaw('0 as unpaid_leave_minutes')
            ->where('employee_id', $employeeId)
            ->where('work_date', '>=', $start->format('Y-m-d'))
            ->where('work_date', '<=', $end->format('Y-m-d'))
            ->where('result_status', '!=', 'absent')
            ->first();

        if ($rows) {
            return [
                'worked_minutes' => (int) $rows->worked_minutes,
                'overtime_minutes' => (int) $rows->overtime_minutes,
                'late_minutes' => (int) $rows->late_minutes,
                'early_leave_minutes' => (int) $rows->early_leave_minutes,
                'paid_leave_minutes' => (int) $rows->paid_leave_minutes,
                'unpaid_leave_minutes' => (int) $rows->unpaid_leave_minutes,
            ];
        }
        return ['worked_minutes'=>0,'overtime_minutes'=>0,'late_minutes'=>0,'early_leave_minutes'=>0,'paid_leave_minutes'=>0,'unpaid_leave_minutes'=>0];
    }
}
