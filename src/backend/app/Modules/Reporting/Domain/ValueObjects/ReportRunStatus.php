<?php

namespace App\Modules\Reporting\Domain\ValueObjects;

enum ReportRunStatus: string
{
    case Requested = "requested";
    case Running = "running";
    case Completed = "completed";
    case Failed = "failed";

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Requested => $target === self::Running,
            self::Running => $target === self::Completed || $target === self::Failed,
            self::Completed, self::Failed => false,
        };
    }
}
