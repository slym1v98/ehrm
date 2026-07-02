<?php

namespace App\Modules\Attendance\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class AttendanceTimesheetModel extends Model
{
    protected $table = 'attendance_timesheets';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'attendance_period_id',
        'employee_id',
        'work_date',
        'shift_assignment_id',
        'expected_minutes',
        'worked_minutes',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'result_status',
        'calculation_run_id',
    ];

    protected $casts = [
        'work_date' => 'date',
        'expected_minutes' => 'integer',
        'worked_minutes' => 'integer',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'overtime_minutes' => 'integer',
    ];
}
