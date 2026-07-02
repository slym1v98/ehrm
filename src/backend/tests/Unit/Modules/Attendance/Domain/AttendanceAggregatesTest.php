<?php

namespace Tests\Unit\Modules\Attendance\Domain;

use App\Modules\Attendance\Domain\Aggregates\AttendanceAdjustmentRequest\AttendanceAdjustmentRequest;
use App\Modules\Attendance\Domain\Aggregates\AttendancePeriod\AttendancePeriod;
use App\Modules\Attendance\Domain\Aggregates\AttendanceRawLog\AttendanceRawLog;
use App\Modules\Attendance\Domain\Aggregates\AttendanceTimesheet\AttendanceTimesheet;
use App\Modules\Attendance\Domain\Exceptions\InvalidAttendanceAdjustmentException;
use App\Modules\Attendance\Domain\ValueObjects\AttendanceStatus;
use App\Modules\Attendance\Domain\ValueObjects\EventType;
use App\Modules\Attendance\Domain\ValueObjects\Source;
use App\Modules\Attendance\Domain\ValueObjects\TimesheetData;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AttendanceAggregatesTest extends TestCase
{
    public function test_raw_log_recording_emits_event(): void
    {
        $rawLog = AttendanceRawLog::record(
            employeeId: 'employee-1',
            source: Source::Web,
            eventType: EventType::CheckIn,
            eventTime: CarbonImmutable::parse('2026-07-02 08:00:00'),
            geoPoint: null,
            payload: [],
        );

        $this->assertCount(1, $rawLog->releaseEvents());
    }

    public function test_timesheet_replace_with_updates_values(): void
    {
        $timesheet = AttendanceTimesheet::fromCalculation(
            periodId: 'period-1',
            employeeId: 'employee-1',
            workDate: CarbonImmutable::parse('2026-07-02'),
            shiftAssignmentId: null,
            data: new TimesheetData(480, 450, 15, 15, 0, AttendanceStatus::Late),
        );

        $timesheet->replaceWith(
            new TimesheetData(480, 480, 0, 0, 0, AttendanceStatus::Present),
            'run-2',
        );

        $this->assertSame(480, $timesheet->workedMinutes());
        $this->assertSame('present', $timesheet->resultStatus()->value);
        $this->assertSame('run-2', $timesheet->calculationRunId());
    }

    public function test_adjustment_cannot_transition_twice(): void
    {
        $adjustment = AttendanceAdjustmentRequest::submit(
            timesheetId: 'timesheet-1',
            employeeId: 'employee-1',
            requestedBy: 'employee-1',
            corrections: ['check_in' => '2026-07-02T08:05:00+07:00'],
            reason: 'Forgot check in',
            evidenceFile: null,
        );

        $adjustment->approve('manager-1', CarbonImmutable::now());

        $this->expectException(InvalidAttendanceAdjustmentException::class);
        $adjustment->reject('manager-2', CarbonImmutable::now());
    }

    public function test_period_close_and_reopen_require_reason(): void
    {
        $period = AttendancePeriod::open(
            periodCode: '2026-07',
            startDate: CarbonImmutable::parse('2026-07-01'),
            endDate: CarbonImmutable::parse('2026-07-31'),
        );

        $period->close();
        $this->assertTrue($period->isClosed());

        $this->expectException(\InvalidArgumentException::class);
        $period->reopen('');
    }

    public function test_period_reopen_emits_event(): void
    {
        $period = AttendancePeriod::open(
            periodCode: '2026-07',
            startDate: CarbonImmutable::parse('2026-07-01'),
            endDate: CarbonImmutable::parse('2026-07-31'),
        );

        $period->close();
        $period->releaseEvents();
        $period->reopen('Need correction window');

        $this->assertSame('reopened', $period->status()->value);
        $this->assertCount(1, $period->releaseEvents());
    }
}
