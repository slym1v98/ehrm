<?php

namespace App\Modules\Leave\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class LeaveRequestModel extends Model
{
    protected $table = 'leave_requests';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'employee_id',
        'leave_type_id',
        'start_at',
        'end_at',
        'duration_unit',
        'duration_minutes',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'balance_before',
    ];

    protected $casts = [
        'start_at' => 'date',
        'end_at' => 'date',
        'duration_minutes' => 'integer',
        'approved_at' => 'datetime',
        'balance_before' => 'integer',
    ];
}
