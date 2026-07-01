<?php

namespace App\Modules\Audit\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLogModel extends Model
{
    use HasUuids;

    protected $table = 'audit_logs';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = [
        'before_payload' => 'array',
        'after_payload' => 'array',
        'occurred_at' => 'datetime',
    ];
}
