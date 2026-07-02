<?php

namespace App\Modules\Payroll\Domain\ValueObjects;

enum PeriodStatus: string
{
    case Open = 'open';
    case Calculating = 'calculating';
    case Completed = 'completed';
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case Locked = 'locked';
    case Published = 'published';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Open => $target === self::Calculating,
            self::Calculating => $target === self::Completed,
            self::Completed => $target === self::Reviewing,
            self::Reviewing => $target === self::Approved || $target === self::Completed,
            self::Approved => $target === self::Locked,
            self::Locked => $target === self::Published || $target === self::Reviewing,
            self::Published => false,
        };
    }
}
