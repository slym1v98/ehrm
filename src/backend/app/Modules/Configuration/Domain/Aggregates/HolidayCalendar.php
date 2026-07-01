<?php

namespace App\Modules\Configuration\Domain\Aggregates;

use App\Modules\Configuration\Infrastructure\Persistence\Eloquent\HolidayCalendarModel;

class HolidayCalendar
{
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly string $name,
        public readonly int $year,
        public readonly bool $active,
        public readonly array $holidays
    ) {}

    public static function fromModel(HolidayCalendarModel $model): self
    {
        return new self(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            year: $model->year,
            active: $model->active,
            holidays: $model->holidays->map(fn ($holiday) => [
                'id' => $holiday->id,
                'date' => $holiday->date->format('Y-m-d'),
                'name' => $holiday->name,
                'paid' => $holiday->paid,
                'metadata' => $holiday->metadata,
            ])->toArray()
        );
    }
}
