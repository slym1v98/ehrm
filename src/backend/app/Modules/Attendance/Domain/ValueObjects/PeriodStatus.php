<?php

namespace App\Modules\Attendance\Domain\ValueObjects;

enum PeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Reopened = 'reopened';
}
