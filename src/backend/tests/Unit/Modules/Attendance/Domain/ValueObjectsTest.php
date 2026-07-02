<?php

namespace Tests\Unit\Modules\Attendance\Domain;

use App\Modules\Attendance\Domain\Aggregates\AttendanceAdjustmentRequest\AttendanceAdjustmentRequestId;
use App\Modules\Attendance\Domain\Aggregates\AttendancePeriod\AttendancePeriodId;
use App\Modules\Attendance\Domain\Aggregates\AttendanceRawLog\AttendanceRawLogId;
use App\Modules\Attendance\Domain\Aggregates\AttendanceTimesheet\AttendanceTimesheetId;
use App\Modules\Attendance\Domain\ValueObjects\AdjustmentStatus;
use App\Modules\Attendance\Domain\ValueObjects\AttendanceStatus;
use App\Modules\Attendance\Domain\ValueObjects\EventType;
use App\Modules\Attendance\Domain\ValueObjects\GeoPoint;
use App\Modules\Attendance\Domain\ValueObjects\PeriodStatus;
use App\Modules\Attendance\Domain\ValueObjects\Source;
use App\Modules\Attendance\Domain\ValueObjects\TimeRange;
use App\Modules\Attendance\Domain\ValueObjects\TimesheetData;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class ValueObjectsTest extends TestCase
{
    public function test_ids_round_trip(): void
    {
        $rawLogId = AttendanceRawLogId::generate();
        $timesheetId = AttendanceTimesheetId::generate();
        $adjustmentId = AttendanceAdjustmentRequestId::generate();
        $periodId = AttendancePeriodId::generate();

        $this->assertSame($rawLogId->toString(), AttendanceRawLogId::fromString($rawLogId->toString())->toString());
        $this->assertSame($timesheetId->toString(), AttendanceTimesheetId::fromString($timesheetId->toString())->toString());
        $this->assertSame($adjustmentId->toString(), AttendanceAdjustmentRequestId::fromString($adjustmentId->toString())->toString());
        $this->assertSame($periodId->toString(), AttendancePeriodId::fromString($periodId->toString())->toString());
    }

    public function test_geo_point_validates_bounds(): void
    {
        $this->assertSame(['lat' => 10.77, 'lng' => 106.69], GeoPoint::fromArray(10.77, 106.69)->toArray());

        $this->expectException(InvalidArgumentException::class);
        GeoPoint::fromArray(100, 0);
    }

    public function test_time_range_duration(): void
    {
        $range = TimeRange::fromTimes(
            CarbonImmutable::parse('2026-07-02 22:00:00'),
            CarbonImmutable::parse('2026-07-03 06:00:00'),
        );

        $this->assertSame(480, $range->durationMinutes());
    }

    public function test_enum_values(): void
    {
        $this->assertSame('present', AttendanceStatus::Present->value);
        $this->assertSame('late', AttendanceStatus::Late->value);
        $this->assertSame('absent', AttendanceStatus::Absent->value);
        $this->assertSame('on_leave', AttendanceStatus::OnLeave->value);
        $this->assertSame('holiday', AttendanceStatus::Holiday->value);
        $this->assertSame('weekend', AttendanceStatus::Weekend->value);
        $this->assertSame('web', Source::Web->value);
        $this->assertSame('manual', Source::Manual->value);
        $this->assertSame('import', Source::Import->value);
        $this->assertSame('device', Source::Device->value);
        $this->assertSame('gps', Source::Gps->value);
        $this->assertSame('check_in', EventType::CheckIn->value);
        $this->assertSame('check_out', EventType::CheckOut->value);
        $this->assertSame('manual', EventType::Manual->value);
        $this->assertSame('pending', AdjustmentStatus::Pending->value);
        $this->assertSame('approved', AdjustmentStatus::Approved->value);
        $this->assertSame('rejected', AdjustmentStatus::Rejected->value);
        $this->assertSame('open', PeriodStatus::Open->value);
        $this->assertSame('closed', PeriodStatus::Closed->value);
        $this->assertSame('reopened', PeriodStatus::Reopened->value);
    }

    public function test_timesheet_data_rejects_negative_minutes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TimesheetData(
            expectedMinutes: 480,
            workedMinutes: -1,
            lateMinutes: 0,
            earlyLeaveMinutes: 0,
            overtimeMinutes: 0,
            status: AttendanceStatus::Absent,
        );
    }

    public function test_timesheet_data_valid_construction(): void
    {
        $data = new TimesheetData(
            expectedMinutes: 480,
            workedMinutes: 450,
            lateMinutes: 15,
            earlyLeaveMinutes: 15,
            overtimeMinutes: 0,
            status: AttendanceStatus::Late,
        );

        $this->assertSame(480, $data->expectedMinutes);
        $this->assertSame('late', $data->status->value);
    }
}
