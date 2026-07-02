<?php

namespace App\Modules\Notification\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class NotificationOutboxModel extends Model
{
    protected $table = 'notification_outbox';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'notification_message_id',
        'channel',
        'status',
        'attempts',
        'max_attempts',
        'available_at',
        'locked_at',
        'locked_by',
        'last_error',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'available_at' => 'datetime',
        'locked_at' => 'datetime',
    ];
}
