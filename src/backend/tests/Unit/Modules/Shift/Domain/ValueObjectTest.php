<?php

namespace Tests\Unit\Modules\Shift\Domain;

use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\RecurrenceRule;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\FlexibilityRules;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\OvertimeRules;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftWindow;
use InvalidArgumentException;
use Tests\TestCase;

class ValueObjectTest extends TestCase
{
    public function test_shift_window_duration_handles_overnight(): void
    {
        $window = ShiftWindow::fromStrings('22:00', '06:00');
        $this->assertTrue($window->isOvernight);
        $this->assertSame(480, $window->durationMinutes());
    }

    public function test_overtime_rules_reject_negative_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OvertimeRules(-1, 15, 0, 0, 0);
    }

    public function test_flexibility_rules_reject_negative_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FlexibilityRules(-5, 0, null, null);
    }

    public function test_invalid_recurrence_frequency_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RecurrenceRule('yearly', 1, [1,2,3], null);
    }
}
