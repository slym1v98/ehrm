<?php

namespace Tests\Unit\Modules\Attendance\Domain;

use App\Modules\Attendance\Domain\Services\AttendanceCalculator;
use App\Modules\Attendance\Domain\ValueObjects\AttendanceStatus;
use App\Modules\Attendance\Domain\ValueObjects\EventType;
use App\Modules\Attendance\Domain\ValueObjects\Source;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AttendanceCalculatorTest extends TestCase
{
    private function rawLog(string $time, string $type, string $source = 'web'): object
    {
        return (object) [
            'eventTime' => str_contains($time,'+1day') ? CarbonImmutable::parse('2026-07-03 '.str_replace('+1day','',$time)) : CarbonImmutable::parse("2026-07-02 {$time}"),
            'eventType' => EventType::from($type),
            'source' => Source::from($source),
        ];
    }

    private function assignment(
        string $start = '08:00',
        string $end = '17:00',
        bool $isOvernight = false,
        int $break = 60,
        int $lateTolerance = 0,
        bool $flexible = false,
        ?object $rules = null,
    ): object {
        $rules = $rules ?? (object) [
            'beforeShiftAllowance' => 0,
            'afterShiftAllowance' => 0,
            'maxEarlyArrival' => 0,
            'maxLateDeparture' => 0,
        ];
        return (object) [
            'shiftTemplate' => (object) [
                'startTime' => $start,
                'endTime' => $end,
                'isOvernight' => $isOvernight,
                'breakMinutes' => $break,
                'lateToleranceMinutes' => $lateTolerance,
                'flexibilityRules' => $flexible ? $rules : null,
                'overtimeRules' => $rules,
                'payrollAttributionRule' => 'start_date',
            ],
        ];
    }

    public function test_overnight_shift_calculation(): void
    {
        $result = AttendanceCalculator::calculate(
            employeeId: 'e1',
            workDate: CarbonImmutable::parse('2026-07-02'),
            rawLogs: [
                $this->rawLog('22:00', 'check_in'),
                $this->rawLog('22:00+1day', 'check_out'),
            ],
            assignment: $this->assignment('22:00', '06:00', true, 0, 0),
            leaves: [],
            holidays: [],
        );

        $this->assertSame(480, $result->expectedMinutes);
        $this->assertSame(AttendanceStatus::Present, $result->status);
    }

    public function test_late_arrival(): void
    {
        $result = AttendanceCalculator::calculate(
            employeeId: 'e1',
            workDate: CarbonImmutable::parse('2026-07-02'),
            rawLogs: [
                $this->rawLog('08:30', 'check_in'),
                $this->rawLog('17:00', 'check_out'),
            ],
            assignment: $this->assignment('08:00', '17:00', false, 60),
            leaves: [],
            holidays: [],
        );

        $this->assertSame(30, $result->lateMinutes);
        $this->assertSame(AttendanceStatus::Late, $result->status);
    }

    public function test_early_leave(): void
    {
        $result = AttendanceCalculator::calculate(
            employeeId: 'e1',
            workDate: CarbonImmutable::parse('2026-07-02'),
            rawLogs: [
                $this->rawLog('08:00', 'check_in'),
                $this->rawLog('16:30', 'check_out'),
            ],
            assignment: $this->assignment('08:00', '17:00', false, 60),
            leaves: [],
            holidays: [],
        );

        $this->assertSame(30, $result->earlyLeaveMinutes);
        $this->assertSame(AttendanceStatus::Present, $result->status);
    }

    public function test_overtime(): void
    {
        $rules = (object) [
            'beforeShiftAllowance' => 0,
            'afterShiftAllowance' => 60,
            'maxEarlyArrival' => 0,
            'maxLateDeparture' => 0,
        ];
        $result = AttendanceCalculator::calculate(
            employeeId: 'e1',
            workDate: CarbonImmutable::parse('2026-07-02'),
            rawLogs: [
                $this->rawLog('08:00', 'check_in'),
                $this->rawLog('18:00', 'check_out'),
            ],
            assignment: $this->assignment('08:00', '17:00', false, 60, 0, false, $rules),
            leaves: [],
            holidays: [],
        );

        $this->assertSame(60, $result->overtimeMinutes);
        $this->assertSame(AttendanceStatus::Present, $result->status);
    }

    public function test_no_raw_logs_absent(): void
    {
        $result = AttendanceCalculator::calculate(
            employeeId: 'e1',
            workDate: CarbonImmutable::parse('2026-07-02'),
            rawLogs: [],
            assignment: $this->assignment('08:00', '17:00'),
            leaves: [],
            holidays: [],
        );

        $this->assertSame(AttendanceStatus::Absent, $result->status);
        $this->assertSame(0, $result->workedMinutes);
    }

    public function test_full_day_leave(): void
    {
        $result = AttendanceCalculator::calculate(
            employeeId: 'e1',
            workDate: CarbonImmutable::parse('2026-07-02'),
            rawLogs: [],
            assignment: $this->assignment('08:00', '17:00'),
            leaves: [
                $this->leaveWindow('2026-07-02 00:00', '2026-07-02 23:59'),
            ],
            holidays: [],
        );

        $this->assertSame(AttendanceStatus::OnLeave, $result->status);
        $this->assertSame(0, $result->workedMinutes);
    }

    public function test_weekend(): void
    {
        $result = AttendanceCalculator::calculate(
            employeeId: 'e1',
            workDate: CarbonImmutable::parse('2026-07-04'), // Saturday
            rawLogs: [],
            assignment: null,
            leaves: [],
            holidays: [CarbonImmutable::parse('2026-07-04')],
        );

        $this->assertSame(AttendanceStatus::Holiday, $result->status);
        $this->assertSame(0, $result->expectedMinutes);
    }

    public function test_partial_leave_reduces_expected(): void
    {
        $result = AttendanceCalculator::calculate(
            employeeId: 'e1',
            workDate: CarbonImmutable::parse('2026-07-02'),
            rawLogs: [
                $this->rawLog('13:00', 'check_in'),
                $this->rawLog('17:00', 'check_out'),
            ],
            assignment: $this->assignment('08:00', '17:00'),
            leaves: [
                $this->leaveWindow('2026-07-02 08:00', '2026-07-02 12:00'),
            ],
            holidays: [],
        );

        $this->assertSame(240, $result->expectedMinutes);
        $this->assertSame(AttendanceStatus::Late, $result->status);
    }


    public function test_flexitime_skips_late_when_min_met(): void
    {
        $flex = (object) [
            'beforeShiftAllowance' => 0,
            'afterShiftAllowance' => 0,
            'maxEarlyArrival' => 30,
            'maxLateDeparture' => 60,
        ];
        $result = AttendanceCalculator::calculate(
            employeeId: 'e1',
            workDate: CarbonImmutable::parse('2026-07-02'),
            rawLogs: [
                $this->rawLog('08:30', 'check_in'),
                $this->rawLog('17:00', 'check_out'),
            ],
            assignment: $this->assignment('08:00', '17:00', false, 60, 0, true, $flex),
            leaves: [],
            holidays: [],
        );

        $this->assertSame(0, $result->lateMinutes);
        $this->assertSame(AttendanceStatus::Present, $result->status);
    }

    private function leaveWindow(string $start, string $end): object
    {
        return (object) [
            'start' => CarbonImmutable::parse($start),
            'end' => CarbonImmutable::parse($end),
        ];
    }
}
