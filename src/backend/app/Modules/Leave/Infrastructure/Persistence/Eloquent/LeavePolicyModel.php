<?php

namespace App\Modules\Leave\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class LeavePolicyModel extends Model
{
    protected $table = 'leave_policies';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'leave_type_id',
        'valid_from',
        'valid_until',
        'max_consecutive_days',
        'requires_attachment',
        'carry_over_limit',
        'carry_over_expiry_months',
        'half_day_allowed',
        'hourly_allowed',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'max_consecutive_days' => 'integer',
        'requires_attachment' => 'boolean',
        'carry_over_limit' => 'integer',
        'carry_over_expiry_months' => 'integer',
        'half_day_allowed' => 'boolean',
        'hourly_allowed' => 'boolean',
    ];
}
