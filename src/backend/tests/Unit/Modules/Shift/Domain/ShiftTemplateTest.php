<?php

namespace Tests\Unit\Modules\Shift\Domain;

use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\FlexibilityRules;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\OvertimeRules;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplate;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftWindow;
use App\Modules\Shift\Domain\Events\ShiftTemplateCreated;
use App\Modules\Shift\Domain\Exceptions\InvalidShiftTemplateException;
use Tests\TestCase;

class ShiftTemplateTest extends TestCase
{
    public function test_create_emits_event(): void
    {
        $template = ShiftTemplate::create(
            ShiftTemplateId::generate(),
            'DAY',
            'Day Shift',
            ShiftWindow::fromStrings('08:00', '17:00'),
            60,
            5,
            new OvertimeRules(30, 15, 0, 0, 120),
            new FlexibilityRules(15, 15, null, null),
            null,
        );

        $events = $template->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ShiftTemplateCreated::class, $events[0]);
        $this->assertTrue($template->active());
    }

    public function test_overnight_requires_payroll_attribution_rule(): void
    {
        $this->expectException(InvalidShiftTemplateException::class);

        ShiftTemplate::create(
            ShiftTemplateId::generate(),
            'NIGHT',
            'Night Shift',
            ShiftWindow::fromStrings('22:00', '06:00'),
            60,
            5,
            new OvertimeRules(30, 15, 0, 0, 120),
            new FlexibilityRules(15, 15, null, null),
            null,
        );
    }

    public function test_break_must_be_less_than_duration(): void
    {
        $this->expectException(InvalidShiftTemplateException::class);

        ShiftTemplate::create(
            ShiftTemplateId::generate(),
            'BAD',
            'Bad Shift',
            ShiftWindow::fromStrings('08:00', '09:00'),
            60,
            5,
            new OvertimeRules(30, 15, 0, 0, 120),
            new FlexibilityRules(15, 15, null, null),
            null,
        );
    }
}
