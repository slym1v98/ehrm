<?php

namespace Tests\Unit\Modules\Attendance\Application;

use App\Modules\Attendance\Application\CommandHandlers\AttendanceRawLog\RecordAttendanceRawLogHandler;
use App\Modules\Attendance\Application\Commands\AttendanceRawLog\RecordAttendanceRawLogCommand;
use App\Modules\Attendance\Domain\Aggregates\AttendancePeriod\AttendancePeriod;
use App\Modules\Attendance\Domain\Aggregates\AttendanceRawLog\AttendanceRawLog;
use App\Modules\Attendance\Domain\Exceptions\AttendancePeriodClosedException;
use App\Modules\Attendance\Domain\Repositories\AttendancePeriodRepositoryInterface;
use App\Modules\Attendance\Domain\Repositories\AttendanceRawLogRepositoryInterface;
use App\Modules\Attendance\Domain\ValueObjects\EventType;
use App\Modules\Attendance\Domain\ValueObjects\Source;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class RecordAttendanceRawLogHandlerTest extends TestCase
{
    public function test_blocks_when_period_closed(): void
    {
        $rawLogRepo = new class implements AttendanceRawLogRepositoryInterface {
            public array $saved = [];
            public function saveAndDispatch(AttendanceRawLog $rawLog): void { $this->saved[] = $rawLog; }
            public function findPaginated(int $perPage = 15, int $page = 1): array { return []; }
            public function findByEmployeeAndRange(string $employeeId, string $from, string $to): array { return []; }
        };

        $periodRepo = new class implements AttendancePeriodRepositoryInterface {
            public function findById(string $id): ?AttendancePeriod { return null; }
            public function findByCode(string $code): ?AttendancePeriod { return null; }
            public function findClosedByDate(string $date): ?AttendancePeriod {
                return AttendancePeriod::open('2026-07', CarbonImmutable::parse('2026-07-01'), CarbonImmutable::parse('2026-07-31'));
            }
            public function saveAndDispatch(AttendancePeriod $period): void {}
            public function findPaginated(int $perPage = 15, int $page = 1): array { return []; }
        };

        $handler = new RecordAttendanceRawLogHandler($rawLogRepo, $periodRepo);

        $this->expectException(AttendancePeriodClosedException::class);
        $handler->handle(new RecordAttendanceRawLogCommand(
            employeeId: 'e1',
            source: Source::Web,
            eventType: EventType::CheckIn,
            eventTime: '2026-07-15T08:00:00+07:00',
            geoPoint: null,
            payload: [],
        ));

        $this->assertSame(0, count($rawLogRepo->saved));
    }

    public function test_saves_when_period_open(): void
    {
        $rawLogRepo = new class implements AttendanceRawLogRepositoryInterface {
            public array $saved = [];
            public function saveAndDispatch(AttendanceRawLog $rawLog): void { $this->saved[] = $rawLog; }
            public function findPaginated(int $perPage = 15, int $page = 1): array { return []; }
            public function findByEmployeeAndRange(string $employeeId, string $from, string $to): array { return []; }
        };

        $periodRepo = new class implements AttendancePeriodRepositoryInterface {
            public function findById(string $id): ?AttendancePeriod { return null; }
            public function findByCode(string $code): ?AttendancePeriod { return null; }
            public function findClosedByDate(string $date): ?AttendancePeriod { return null; }
            public function saveAndDispatch(AttendancePeriod $period): void {}
            public function findPaginated(int $perPage = 15, int $page = 1): array { return []; }
        };

        $handler = new RecordAttendanceRawLogHandler($rawLogRepo, $periodRepo);

        $handler->handle(new RecordAttendanceRawLogCommand(
            employeeId: 'e1',
            source: Source::Web,
            eventType: EventType::CheckIn,
            eventTime: '2026-07-15T08:00:00+07:00',
            geoPoint: null,
            payload: [],
        ));

        $this->assertSame(1, count($rawLogRepo->saved));
    }
}
