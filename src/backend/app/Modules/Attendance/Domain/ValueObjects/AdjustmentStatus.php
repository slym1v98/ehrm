<?php

namespace App\Modules\Attendance\Domain\ValueObjects;

enum AdjustmentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
