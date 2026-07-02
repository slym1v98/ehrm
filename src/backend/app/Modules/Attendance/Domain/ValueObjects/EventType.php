<?php

namespace App\Modules\Attendance\Domain\ValueObjects;

enum EventType: string
{
    case CheckIn = 'check_in';
    case CheckOut = 'check_out';
    case Manual = 'manual';
}
