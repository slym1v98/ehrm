<?php

namespace App\Modules\Attendance\Domain\ValueObjects;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case OnLeave = 'on_leave';
    case Holiday = 'holiday';
    case Weekend = 'weekend';
}
