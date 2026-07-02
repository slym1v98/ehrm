<?php

namespace App\Modules\Attendance\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class AttendanceRawLogModel extends Model
{
    protected $table = 'attendance_raw_logs';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'id',
        'employee_id',
        'source',
        'event_type',
        'event_time',
        'geo_point',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'geo_point' => 'array',
        'payload' => 'array',
        'created_at' => 'datetime',
    ];
}
