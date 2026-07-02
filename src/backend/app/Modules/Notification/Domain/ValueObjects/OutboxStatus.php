<?php

namespace App\Modules\Notification\Domain\ValueObjects;

enum OutboxStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Sent = 'sent';
    case Failed = 'failed';
}
