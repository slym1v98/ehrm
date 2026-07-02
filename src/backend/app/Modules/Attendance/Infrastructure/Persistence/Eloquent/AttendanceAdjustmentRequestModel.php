<?php

namespace App\Modules\Attendance\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class AttendanceAdjustmentRequestModel extends Model
{
    protected $table = 'attendance_adjustment_requests';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'attendance_timesheet_id',
        'employee_id',
        'requested_by',
        'reason',
        'evidence_file',
        'corrections',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'corrections' => 'array',
        'approved_at' => 'datetime',
    ];
}
