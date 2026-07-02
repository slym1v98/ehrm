<?php

namespace App\Modules\Notification\Domain\ValueObjects;

enum MessageStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
}
