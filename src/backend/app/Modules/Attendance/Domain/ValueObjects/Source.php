<?php

namespace App\Modules\Attendance\Domain\ValueObjects;

enum Source: string
{
    case Web = 'web';
    case Manual = 'manual';
    case Import = 'import';
    case Device = 'device';
    case Gps = 'gps';
}
