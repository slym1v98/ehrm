<?php

namespace App\Modules\Attendance\Domain\Services;

use App\Modules\Attendance\Domain\ValueObjects\AttendanceStatus;
use App\Modules\Attendance\Domain\ValueObjects\TimesheetData;
use Carbon\CarbonImmutable;

final class AttendanceCalculator
{
    public static function calculate(
        string $employeeId,
        CarbonImmutable $workDate,
        array $rawLogs,
        ?object $assignment,
        array $leaves,
        array $holidays,
    ): TimesheetData {
        foreach ($holidays as $holiday) {
            if ($holiday instanceof CarbonImmutable && $holiday->isSameDay($workDate)) {
                return new TimesheetData(0, 0, 0, 0, 0, AttendanceStatus::Holiday);
            }
        }

        if ($workDate->isWeekend()) {
            return new TimesheetData(0, 0, 0, 0, 0, AttendanceStatus::Weekend);
        }

        if ($assignment === null) {
            return new TimesheetData(0, 0, 0, 0, 0, AttendanceStatus::Absent);
        }

        $shiftStart = self::timeOnDate($assignment->shiftTemplate->startTime, $workDate);
        $shiftEnd = self::timeOnDate($assignment->shiftTemplate->endTime, $workDate);
        if (($assignment->shiftTemplate->isOvernight ?? false) && $shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd = $shiftEnd->addDay();
        }

        $expected = max(0, $shiftStart->diffInMinutes($shiftEnd) - ($assignment->shiftTemplate->breakMinutes ?? 0));

        $leaveCoveredMinutes = 0;
        foreach ($leaves as $leave) {
            $leaveStart = $leave->start;
            $leaveEnd = $leave->end;
            if ($leaveStart <= $shiftEnd && $leaveEnd >= $shiftStart) {
                $overlapStart = $leaveStart->greaterThan($shiftStart) ? $leaveStart : $shiftStart;
                $overlapEnd = $leaveEnd->lessThan($shiftEnd) ? $leaveEnd : $shiftEnd;
                if ($overlapEnd->greaterThan($overlapStart)) {
                    $leaveCoveredMinutes += $overlapStart->diffInMinutes($overlapEnd);
                }
            }
        }

        if ($leaveCoveredMinutes >= $expected && $expected > 0) {
            return new TimesheetData($expected, 0, 0, 0, 0, AttendanceStatus::OnLeave);
        }

        if (empty($rawLogs)) {
            return new TimesheetData(max(0, $expected - $leaveCoveredMinutes), 0, 0, 0, 0, AttendanceStatus::Absent);
        }

        usort($rawLogs, fn ($a, $b) => $a->eventTime <=> $b->eventTime);

        $workedMinutes = 0;
        $firstCheckIn = null;
        $lastCheckOut = null;

        for ($i = 0; $i < count($rawLogs); $i += 2) {
            $in = $rawLogs[$i] ?? null;
            $out = $rawLogs[$i + 1] ?? null;

            if ($in === null) {
                continue;
            }

            $firstCheckIn ??= $in->eventTime;

            if ($out === null) {
                $outTime = $shiftEnd;
            } else {
                $outTime = $out->eventTime;
                $lastCheckOut = $outTime;
            }

            if ($outTime->greaterThan($in->eventTime)) {
                $workedMinutes += $in->eventTime->diffInMinutes($outTime);
            }
        }

        $isFlexible = isset($assignment->shiftTemplate->flexibilityRules) && $assignment->shiftTemplate->flexibilityRules !== null;
        $lateMinutes = 0;
        $earlyLeaveMinutes = 0;

        if (!$isFlexible && $firstCheckIn !== null && $firstCheckIn->greaterThan($shiftStart)) {
            $lateMinutes = $shiftStart->diffInMinutes($firstCheckIn);
        }

        if (!$isFlexible && $lastCheckOut !== null && $shiftEnd->greaterThan($lastCheckOut)) {
            $earlyLeaveMinutes = $lastCheckOut->diffInMinutes($shiftEnd);
        }

        $expectedAfterLeave = max(0, $expected - $leaveCoveredMinutes);
        $overtimeMinutes = max(0, $workedMinutes - $expectedAfterLeave);
        if ($overtimeMinutes > 0) {
            $otRules = $assignment->shiftTemplate->overtimeRules ?? null;
            $maxOT = 0;
            if ($otRules) {
                $maxOT = ($otRules->beforeShiftAllowance ?? 0) + ($otRules->afterShiftAllowance ?? 0);
            }
            if ($maxOT > 0) {
                $overtimeMinutes = min($overtimeMinutes, $maxOT);
            }
        }
        $status = AttendanceStatus::Present;

        if ($workedMinutes === 0) {
            $status = $leaveCoveredMinutes > 0 ? AttendanceStatus::OnLeave : AttendanceStatus::Absent;
        } elseif (!$isFlexible && $lateMinutes > 0) {
            $status = AttendanceStatus::Late;
        }

        // ponytail: Flexitime simplified — no core-hours enforcement; add when Phase 4 rules engine exists.
        return new TimesheetData(
            expectedMinutes: $expectedAfterLeave,
            workedMinutes: min($workedMinutes, $expectedAfterLeave),
            lateMinutes: $lateMinutes,
            earlyLeaveMinutes: $earlyLeaveMinutes,
            overtimeMinutes: $overtimeMinutes,
            status: $status,
        );
    }

    private static function timeOnDate(string $time, CarbonImmutable $date): CarbonImmutable
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, 0);
        return $date->setTime((int) $hour, (int) $minute, 0);
    }
}
