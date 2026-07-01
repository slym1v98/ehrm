<?php

namespace App\Modules\Organization\Domain\Aggregates\Position;

enum PositionStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isInactive(): bool
    {
        return $this === self::Inactive;
    }
}
