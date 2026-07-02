<?php

namespace App\Modules\Notification\Domain\ValueObjects;

enum Channel: string
{
    case InApp = 'in_app';
    case Email = 'email';
    case Sms = 'sms';
}
