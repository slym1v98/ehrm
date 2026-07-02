<?php

namespace App\Modules\Notification\Domain\ValueObjects;

enum NotificationPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Normal => 2,
            self::High => 3,
        };
    }
}
