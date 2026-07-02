<?php

namespace App\Modules\Payroll\Domain\ValueObjects;

enum AdjustmentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => $target === self::Approved || $target === self::Rejected,
            self::Approved, self::Rejected => false,
        };
    }
}
