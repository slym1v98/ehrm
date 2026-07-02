<?php

namespace App\Modules\Attendance\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class GeoPoint
{
    private function __construct(public float $lat, public float $lng)
    {
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException('Latitude out of bounds');
        }

        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException('Longitude out of bounds');
        }
    }

    public static function fromArray(float $lat, float $lng): self
    {
        return new self($lat, $lng);
    }

    public function toArray(): array
    {
        return ['lat' => $this->lat, 'lng' => $this->lng];
    }
}
