<?php

namespace App\Modules\Leave\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class LeaveTypeModel extends Model
{
    protected $table = 'leave_types';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'code',
        'name',
        'is_balance_tracked',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_balance_tracked' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
