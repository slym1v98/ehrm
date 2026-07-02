<?php

namespace App\Modules\Notification\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class MessageTemplateModel extends Model
{
    protected $table = 'notification_templates';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'code',
        'name',
        'channel',
        'subject',
        'body',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];
}
