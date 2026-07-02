<?php
namespace Tests\Unit\Modules\Leave\Domain;
use App\Modules\Leave\Domain\ValueObjects\DurationUnit;
use App\Modules\Leave\Domain\ValueObjects\LeavePeriod;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class LeavePeriodTest extends TestCase
{
    public function test_overlap(): void
    {
        $a = new LeavePeriod(CarbonImmutable::parse('2026-07-15'), CarbonImmutable::parse('2026-07-17'), DurationUnit::DAY, 1440);
        $b = new LeavePeriod(CarbonImmutable::parse('2026-07-16'), CarbonImmutable::parse('2026-07-18'), DurationUnit::DAY, 1440);
        $this->assertTrue($a->overlaps($b));
        $this->assertTrue($b->overlaps($a));
    }

    public function test_no_overlap(): void
    {
        $a = new LeavePeriod(CarbonImmutable::parse('2026-07-15'), CarbonImmutable::parse('2026-07-16'), DurationUnit::DAY, 960);
        $b = new LeavePeriod(CarbonImmutable::parse('2026-07-17'), CarbonImmutable::parse('2026-07-18'), DurationUnit::DAY, 960);
        $this->assertFalse($a->overlaps($b));
    }

    public function test_end_before_start_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LeavePeriod(CarbonImmutable::parse('2026-07-17'), CarbonImmutable::parse('2026-07-15'), DurationUnit::DAY, 480);
    }
}
