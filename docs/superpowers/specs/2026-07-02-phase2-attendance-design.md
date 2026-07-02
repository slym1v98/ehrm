# Phase 2 Attendance BC Design

Version: 0.1
Date: 2026-07-02
Status: Design approved (brainstorming)

## 1. Scope

Build Attendance module (`app/Modules/Attendance/`) as the second Phase 2 module. Covers raw attendance logs, calculated timesheets, correction requests with inline approval, and monthly attendance periods.

**In scope:** `AttendanceRawLog` recording (multi-source), `AttendanceTimesheet` calculation with an `AttendanceCalculator` domain service (overnight, flexitime, late/early/OT, leave-aware), `AttendanceAdjustmentRequest` submit/approve/reject with inline approval (no Workflow BC dependency), `AttendancePeriod` open/close/reopen, permission integration with Identity module, full test suite.

**Out of scope:** Workflow BC integration (adjustment approval uses inline `approved_by`/`approved_at` fields; migrating to Workflow BC deferred), leave module (attendance consumes leave reads only), payroll integration, real-time device SDK integrations, GPS geofencing enforcement, mobile push, partitioning of `attendance_raw_logs`.

## 2. Architecture

Strict DDD tactical pattern with 3 layers, mirroring Shift/Employee modules.

```
Module/Attendance/
  Domain/         — Pure PHP, no Laravel deps
  Application/    — Commands/Handlers + Queries
  Infrastructure/ — Eloquent, HTTP controllers, seeders, routes
```

**Dependency:** Domain ← Application ← Infrastructure.

**Difference from Shift:** an `AttendanceCalculator` domain service holds calculation logic (overnight, flexitime, late/early/OT). Adjustments use inline approval fields (`status`, `approved_by`, `approved_at`) instead of a `WorkflowRequest`; migration to the Workflow BC is deferred.

## 3. Module Layout

```
app/Modules/Attendance/
  Domain/
    Aggregates/
      AttendanceRawLog/
        AttendanceRawLog.php, AttendanceRawLogId.php
      AttendanceTimesheet/
        AttendanceTimesheet.php, AttendanceTimesheetId.php
      AttendanceAdjustmentRequest/
        AttendanceAdjustmentRequest.php, AttendanceAdjustmentRequestId.php
      AttendancePeriod/
        AttendancePeriod.php, AttendancePeriodId.php
    Services/
      AttendanceCalculator.php
    ValueObjects/
      GeoPoint.php, TimeRange.php, AttendanceStatus.php
      Source.php, EventType.php, AdjustmentStatus.php, PeriodStatus.php
      TimesheetData.php
    Events/
      AttendanceRawLogRecorded.php, AttendanceCalculated.php
      AttendanceAdjustmentRequested.php, AttendanceAdjustmentApproved.php, AttendanceAdjustmentRejected.php
      AttendancePeriodOpened.php, AttendancePeriodClosed.php, AttendancePeriodReopened.php
    Repositories/
      AttendanceRawLogRepositoryInterface.php
      AttendanceTimesheetRepositoryInterface.php
      AttendanceAdjustmentRequestRepositoryInterface.php
      AttendancePeriodRepositoryInterface.php
    Exceptions/
      AttendanceRawLogNotFoundException.php
      AttendanceTimesheetNotFoundException.php
      AttendancePeriodClosedException.php
      AttendancePeriodNotFoundException.php
      DuplicatePendingAdjustmentException.php
      InvalidAttendanceAdjustmentException.php
      InvalidAttendanceCalculationException.php
  Application/
    Commands/AttendanceRawLog/
      RecordAttendanceRawLogCommand.php
    CommandHandlers/AttendanceRawLog/
      RecordAttendanceRawLogHandler.php
    Commands/AttendanceTimesheet/
      CalculateAttendanceForPeriodCommand.php
      RecalculateAttendanceForEmployeeCommand.php
    CommandHandlers/AttendanceTimesheet/...
    Commands/AttendanceAdjustment/
      SubmitAttendanceAdjustmentCommand.php
      ApproveAttendanceAdjustmentCommand.php
      RejectAttendanceAdjustmentCommand.php
    CommandHandlers/AttendanceAdjustment/...
    Commands/AttendancePeriod/
      OpenAttendancePeriodCommand.php
      CloseAttendancePeriodCommand.php
      ReopenAttendancePeriodCommand.php
    CommandHandlers/AttendancePeriod/...
    Queries/
      GetAttendanceTimesheetQuery.php
      GetEmployeeAttendanceQuery.php
      ListAttendanceRawLogsQuery.php
      ListPendingAdjustmentsQuery.php
      ListAttendancePeriodsQuery.php
    QueryHandlers/...
  Infrastructure/
    Persistence/
      Eloquent/
        AttendanceRawLogModel.php, AttendanceTimesheetModel.php,
        AttendanceAdjustmentRequestModel.php, AttendancePeriodModel.php
      Repositories/
        EloquentAttendanceRawLogRepository.php, EloquentAttendanceTimesheetRepository.php,
        EloquentAttendanceAdjustmentRequestRepository.php, EloquentAttendancePeriodRepository.php
    Http/
      Controllers/
        AttendanceRawLogController.php, AttendanceTimesheetController.php,
        AttendanceAdjustmentController.php, AttendancePeriodController.php
      Requests/
        RecordAttendanceRawLogRequest.php, CalculateAttendanceRequest.php,
        SubmitAttendanceAdjustmentRequest.php, OpenAttendancePeriodRequest.php,
        ReopenAttendancePeriodRequest.php
      Resources/
        AttendanceRawLogResource.php, AttendanceTimesheetResource.php,
        AttendanceAdjustmentRequestResource.php, AttendancePeriodResource.php
    Seeders/AttendancePermissionSeeder.php
  Routes/api.php
```

## 4. Domain Model

### 4.1 AttendanceRawLog

```
AttendanceRawLog {
  id: AttendanceRawLogId (UUID VO)
  employeeId: EmployeeId (UUID ref, Employee module)
  source: Source (enum: web|manual|import|device|gps)
  eventType: EventType (enum: check_in|check_out|manual)
  eventTime: CarbonImmutable
  geoPoint: ?GeoPoint (VO — {lat, lng})
  payload: array

  static record(employeeId, source, eventType, eventTime, geoPoint, payload): self

  Invariants:
  - Append-only. No update/delete after persist.
}
```

### 4.2 AttendanceTimesheet

```
AttendanceTimesheet {
  id: AttendanceTimesheetId (UUID VO)
  attendancePeriodId: AttendancePeriodId
  employeeId: EmployeeId
  workDate: CarbonImmutable (date-only)
  shiftAssignmentId: ?ShiftAssignmentId (nullable — no-shift days)
  expectedMinutes: int
  workedMinutes: int
  lateMinutes: int
  earlyLeaveMinutes: int
  overtimeMinutes: int
  resultStatus: AttendanceStatus (VO — present|late|absent|on_leave|holiday|weekend)
  calculationRunId: ?string

  static fromCalculation(periodId, employeeId, workDate, shiftAssignmentId, TimesheetData): self
  replaceWith(TimesheetData, calculationRunId): void  // idempotent recalc

  Invariants:
  - One timesheet per (employeeId, workDate, attendancePeriodId).
  - Recalculation replaces values in place; audit trail via events + calculationRunId.
}
```

### 4.3 AttendanceAdjustmentRequest

```
AttendanceAdjustmentRequest {
  id: AttendanceAdjustmentRequestId (UUID VO)
  attendanceTimesheetId: AttendanceTimesheetId
  employeeId: EmployeeId
  requestedBy: EmployeeId
  reason: string
  evidenceFile: ?string
  corrections: array (field => newValue)
  status: AdjustmentStatus (enum: pending|approved|rejected)
  approvedBy: ?EmployeeId
  approvedAt: ?CarbonImmutable

  static submit(timesheetId, employeeId, requestedBy, corrections, reason, evidenceFile): self
  approve(approverId, at): void
  reject(approverId, at): void

  Invariants:
  - Only one pending request per timesheet (partial unique index at DB).
  - Cannot submit against a timesheet in a closed period.
  - Transitions: pending→approved, pending→rejected only.
}
```

### 4.4 AttendancePeriod

```
AttendancePeriod {
  id: AttendancePeriodId (UUID VO)
  periodCode: string (unique, e.g. "2026-07")
  startDate: CarbonImmutable (date)
  endDate: CarbonImmutable (date)
  status: PeriodStatus (enum: open|closed|reopened)

  static open(periodCode, startDate, endDate): self
  close(): void
  reopen(reason): void

  Invariants:
  - startDate <= endDate.
  - Closed period blocks new adjustments and new raw log ingestion for dates inside the period.
  - Reopen requires explicit reason and emits event.
}
```

### 4.5 Value Objects

- `GeoPoint` — { lat: float, lng: float } with basic bounds validation.
- `TimeRange` — { start: CarbonImmutable, end: CarbonImmutable, duration(): int minutes }.
- `AttendanceStatus` — enum: `present|late|absent|on_leave|holiday|weekend`.
- `Source` — enum: `web|manual|import|device|gps`.
- `EventType` — enum: `check_in|check_out|manual`.
- `AdjustmentStatus` — enum: `pending|approved|rejected`.
- `PeriodStatus` — enum: `open|closed|reopened`.
- `TimesheetData` — DTO returned by `AttendanceCalculator` (expected/worked/late/early/OT/status).

### 4.6 Domain Events

- `AttendanceRawLogRecorded` — { rawLogId, employeeId, eventType, eventTime }
- `AttendanceCalculated` — { timesheetId, employeeId, workDate, resultStatus }
- `AttendanceAdjustmentRequested` — { requestId, timesheetId, employeeId }
- `AttendanceAdjustmentApproved` — { requestId, timesheetId, approvedBy }
- `AttendanceAdjustmentRejected` — { requestId, timesheetId, approvedBy }
- `AttendancePeriodOpened` — { periodId, periodCode }
- `AttendancePeriodClosed` — { periodId, periodCode }
- `AttendancePeriodReopened` — { periodId, reason }

### 4.7 Domain Service: AttendanceCalculator

Pure PHP, stateless, no Laravel deps.

```
AttendanceCalculator::calculate(
    EmployeeId $employeeId,
    CarbonImmutable $workDate,
    Collection<AttendanceRawLog> $rawLogs,     // sorted by event_time
    ?ShiftAssignment $assignment,               // read-model from Shift BC
    Collection<ApprovedLeaveWindow> $leaves,    // read-model from Leave BC (may be empty)
    Collection<CarbonImmutable> $holidays       // holiday dates from Configuration
): TimesheetData
```

Resolution:
1. **Holiday/weekend first.** If `workDate` in holidays or is Saturday/Sunday → `holiday|weekend`; `expected = 0`.
2. **No assignment.** No shift → `absent` with zeros; HR adjusts manually.
3. **Full-day leave.** Approved leave covers whole shift → `on_leave`; `worked = 0`, all zeros.
4. **Has assignment + raw logs (main path).**
   - `ShiftWindow.duration() = expectedMinutes`.
   - Pair check-in/check-out. Odd count → last unpaired open event ends at shift end (or is discarded — decided per test).
   - `workedMinutes = sum(pair durations)`.
   - `lateMinutes = max(0, firstCheckIn - shiftStart)` when not flexible.
   - `earlyLeaveMinutes = max(0, shiftEnd - lastCheckOut)` when not flexible.
   - `overtimeMinutes = max(0, workedMinutes - expectedMinutes)` clamped by `OvertimeRules.beforeShiftAllowance/afterShiftAllowance` when configured.
5. **Overnight.** `ShiftWindow.isOvernight` — a check-out on the next calendar day is attributed to the shift start date's timesheet. Payroll attribution follows `ShiftTemplate.payrollAttributionRule` (already enforced in Shift BC).
6. **Flexitime.** `FlexibilityRules` present → skip late/early; require worked minutes ≥ `duration - maxEarlyArrival - maxLateDeparture`; otherwise `absent`.
7. **Partial leave.** Approved leave covers part of the shift → subtract the covered minutes from `expectedMinutes`; remainder matched against raw logs.

Returns `TimesheetData`.

`ponytail:` Flexitime simplified — no core-hours enforcement, no rotation groups. Add when a full rule engine ships in Phase 4.

## 5. Database Schema

### attendance_periods

```sql
CREATE TABLE attendance_periods (
    id          UUID PRIMARY KEY,
    period_code VARCHAR(20) NOT NULL UNIQUE,
    start_date  DATE NOT NULL,
    end_date    DATE NOT NULL,
    status      VARCHAR(20) NOT NULL DEFAULT 'open',
    created_at  TIMESTAMP NOT NULL,
    updated_at  TIMESTAMP NOT NULL
);
CREATE INDEX idx_attendance_periods_status ON attendance_periods(status);
```

### attendance_raw_logs

```sql
CREATE TABLE attendance_raw_logs (
    id          UUID PRIMARY KEY,
    employee_id UUID NOT NULL,
    source      VARCHAR(20) NOT NULL,      -- web|manual|import|device|gps
    event_type  VARCHAR(20) NOT NULL,      -- check_in|check_out|manual
    event_time  TIMESTAMPTZ NOT NULL,
    geo_point   JSONB NULL,
    payload     JSONB NOT NULL DEFAULT '{}',
    created_at  TIMESTAMP NOT NULL
);
CREATE INDEX idx_raw_logs_employee_time ON attendance_raw_logs(employee_id, event_time);
CREATE INDEX idx_raw_logs_source_time ON attendance_raw_logs(source, event_time);
```

`ponytail:` Monthly partitioning skipped (YAGNI). Add when raw log volume > ~10M rows or query latency degrades.

### attendance_timesheets

```sql
CREATE TABLE attendance_timesheets (
    id                    UUID PRIMARY KEY,
    attendance_period_id  UUID NOT NULL REFERENCES attendance_periods(id),
    employee_id           UUID NOT NULL,
    work_date             DATE NOT NULL,
    shift_assignment_id   UUID NULL,
    expected_minutes      INT NOT NULL DEFAULT 0,
    worked_minutes        INT NOT NULL DEFAULT 0,
    late_minutes          INT NOT NULL DEFAULT 0,
    early_leave_minutes   INT NOT NULL DEFAULT 0,
    overtime_minutes      INT NOT NULL DEFAULT 0,
    result_status         VARCHAR(20) NOT NULL,
    calculation_run_id    VARCHAR(50) NULL,
    created_at            TIMESTAMP NOT NULL,
    updated_at            TIMESTAMP NOT NULL,
    UNIQUE (employee_id, work_date, attendance_period_id)
);
CREATE INDEX idx_timesheets_period ON attendance_timesheets(attendance_period_id);
CREATE INDEX idx_timesheets_employee_date ON attendance_timesheets(employee_id, work_date);
```

### attendance_adjustment_requests

```sql
CREATE TABLE attendance_adjustment_requests (
    id                        UUID PRIMARY KEY,
    attendance_timesheet_id   UUID NOT NULL REFERENCES attendance_timesheets(id),
    employee_id               UUID NOT NULL,
    requested_by              UUID NOT NULL,
    reason                    TEXT NOT NULL,
    evidence_file             VARCHAR(500) NULL,
    corrections               JSONB NOT NULL,
    status                    VARCHAR(20) NOT NULL DEFAULT 'pending',
    approved_by               UUID NULL,
    approved_at               TIMESTAMPTZ NULL,
    created_at                TIMESTAMP NOT NULL,
    updated_at                TIMESTAMP NOT NULL
);
CREATE INDEX idx_adj_req_timesheet ON attendance_adjustment_requests(attendance_timesheet_id);
CREATE INDEX idx_adj_req_status ON attendance_adjustment_requests(status);
CREATE UNIQUE INDEX uniq_adj_req_pending
  ON attendance_adjustment_requests(attendance_timesheet_id)
  WHERE status = 'pending';
```

Migration filenames:

- `2026_07_02_050001_create_attendance_periods_table.php`
- `2026_07_02_050002_create_attendance_raw_logs_table.php`
- `2026_07_02_050003_create_attendance_timesheets_table.php`
- `2026_07_02_050004_create_attendance_adjustment_requests_table.php`

## 6. API Design

Route prefix `/api/v1`, Sanctum auth + `permission` middleware.

### 6.1 Raw logs

| Method | Path | Permission | Notes |
|--------|------|------------|-------|
| POST | `/attendance/raw-logs` | `attendance.raw-log.create` | Record check-in/out/manual event |
| GET  | `/attendance/raw-logs` | `attendance.raw-log.view`   | List / filter logs |

Create request:

```json
{
  "employee_id": "uuid",
  "source": "web",
  "event_type": "check_in",
  "event_time": "2026-07-02T08:00:00+07:00",
  "geo_point": {"lat": 10.77, "lng": 106.69},
  "payload": {}
}
```

### 6.2 Timesheets

| Method | Path | Permission | Notes |
|--------|------|------------|-------|
| GET  | `/attendance/timesheets` | `attendance.timesheet.view` | List / filter rows |
| GET  | `/employees/{id}/attendance` | `attendance.timesheet.view` | Employee calendar view |
| POST | `/attendance/calculate` | `attendance.timesheet.calculate` | Calculate / recalculate range |

Calculate request:

```json
{
  "employee_id": "uuid",
  "from": "2026-07-01",
  "to": "2026-07-31"
}
```

### 6.3 Adjustments

| Method | Path | Permission | Notes |
|--------|------|------------|-------|
| POST | `/attendance-adjustment-requests` | `attendance.adjustment.create`  | Submit correction |
| GET  | `/attendance-adjustment-requests` | `attendance.adjustment.approve` | List pending |
| POST | `/attendance-adjustment-requests/{id}/approve` | `attendance.adjustment.approve` | Approve + trigger recalculation |
| POST | `/attendance-adjustment-requests/{id}/reject`  | `attendance.adjustment.approve` | Reject |

Submit request:

```json
{
  "attendance_timesheet_id": "uuid",
  "corrections": {
    "check_in": "2026-07-02T08:05:00+07:00",
    "check_out": "2026-07-02T17:30:00+07:00"
  },
  "reason": "Forgot checkout",
  "evidence_file": null
}
```

### 6.4 Periods

| Method | Path | Permission | Notes |
|--------|------|------------|-------|
| POST | `/attendance-periods` | `attendance.period.manage` | Open / create period |
| POST | `/attendance-periods/{id}/close` | `attendance.period.manage` | Close period |
| POST | `/attendance-periods/{id}/reopen` | `attendance.period.manage` | Reopen with reason |
| GET  | `/attendance-periods` | `attendance.period.manage` | List |

### 6.5 Response Shape

Same as other modules: `{ "data": {...} }` for single resource; `{ "data": [...], "meta": {...}, "links": {...} }` for lists.

### 6.6 Error Handling

Domain exceptions extend shared `AppException`:

- `AttendancePeriodClosedException` → 422
- `AttendanceTimesheetNotFoundException` → 404
- `AttendanceRawLogNotFoundException` → 404
- `AttendancePeriodNotFoundException` → 404
- `DuplicatePendingAdjustmentException` → 409
- `InvalidAttendanceAdjustmentException` → 422
- `InvalidAttendanceCalculationException` → 422

## 7. Permissions

```php
['attendance.raw-log.create',       'raw-log',    'create'],
['attendance.raw-log.view',         'raw-log',    'view'],
['attendance.timesheet.view',       'timesheet',  'view'],
['attendance.timesheet.calculate',  'timesheet',  'calculate'],
['attendance.adjustment.create',    'adjustment', 'create'],
['attendance.adjustment.approve',   'adjustment', 'approve'],
['attendance.period.manage',        'period',     'manage'],
```

Grant all `attendance.*` to `SUPER_ADMIN` and `HR_MANAGER`.

## 8. Testing Strategy

| Layer | Approach | Count |
|-------|----------|-------|
| Domain unit | VO validation, calculator (overnight, flexitime, late/early/OT, partial leave, no-assignment), period status machine | ~15 |
| Application | Handlers with fake repos: record log, submit/approve/reject adjustment, calculate range, open/close/reopen period | ~8 |
| Feature HTTP | Full API + permission enforcement (401/403) across all endpoints | ~10 |
| **Total** | | **~33** |

Key calculator test cases:

- Overnight shift: check-in 22:00, check-out next-day 06:00 → worked 480 min.
- Late arrival: check-in 08:30 vs shift start 08:00 → late 30 min.
- Early leave: check-out 16:30 vs shift end 17:00 → early 30 min.
- Overtime: check-out 18:00 for 08–17 shift → OT 60 min (within allowance).
- Flexitime — no late/early but min hours met → `present`.
- No raw logs + no leave + assigned shift → `absent`.
- Full-day leave → `on_leave`, worked 0.
- Weekend / configured holiday → `weekend|holiday`, expected 0.
- Partial leave AM, work PM → `expectedMinutes` reduced by leave, remainder matched.

## 9. Acceptance Criteria

1. Raw log recorded via API for all sources (`web|manual|import|device|gps`) and event types.
2. Calculator produces correct timesheet for standard, overnight, flexitime, no-assignment, leave-covered, holiday/weekend, partial-leave scenarios.
3. Adjustment submission against a timesheet in a closed period → 422.
4. Adjustment approval triggers automatic recalculation of the target timesheet.
5. Duplicate pending adjustment on the same timesheet → 409.
6. Period close prevents new adjustments and rejects new raw logs targeting dates inside the closed period.
7. Period reopen requires an explicit reason and emits `AttendancePeriodReopened`.
8. All `attendance.*` permissions seeded; `SUPER_ADMIN` and `HR_MANAGER` roles grant them.
9. All Attendance tests pass; full backend suite green.
10. Module structure matches Shift / Employee (Domain / Application / Infrastructure).

## 10. Implementation Order

1. Migrations (4 files).
2. Eloquent models with UUID keys and JSONB / date casts.
3. Domain layer: value objects → events → exceptions → aggregates → `AttendanceCalculator`.
4. Repository interfaces + Eloquent implementations + DI bindings in `AppServiceProvider`.
5. Application layer: commands, handlers, queries, query handlers.
6. HTTP layer: FormRequests, resources, controllers, module routes, wire into `routes/api.php`.
7. Seeders: extend `PermissionSeeder` and `RoleSeeder` in Identity module with `attendance.*`.
8. Test suite: domain unit → application → feature HTTP.
9. Module README with aggregates, calculator rules, endpoints, permissions, test commands.

## 11. Dependencies

- **Identity** — permission checks, seeder extension.
- **Employee** — validates `employee_id` values.
- **Shift** — calculator reads `ShiftAssignment` and `ShiftTemplate` via repository interfaces (already merged).
- **Configuration** — holiday calendar reads (basic set; empty collection acceptable initially if not seeded).
- **Audit** — subscribes to Attendance events through existing listener pattern.

## 12. Risks

- **Calculator edge cases** — overnight + flexitime + partial-leave combinations increase test surface. Mitigated by pure-PHP service (no framework coupling), TDD, and encapsulated logic that can be extracted or replaced without ripple.
- **Period vs adjustment race** — concurrent close + adjustment submission. Mitigated by domain guard (`AttendancePeriod.status` checked in the handler) plus application-layer transaction; Redis locking deferred until scale demands it.
- **Shift read model** — the calculator depends on Shift BC read-model shape (`ShiftAssignment` + `ShiftTemplate`). Shift BC is already merged; contract is stable but any change requires calculator updates.
- **Leave module absence** — Leave BC not built yet; calculator accepts an empty leave collection for now. When Leave BC lands, wire real reads without changing the calculator signature.
- **Inline adjustment approval migration** — approval fields live on the adjustment table. If Workflow BC later owns approvals, migration will move `status/approved_by/approved_at` into `workflow_requests`; the adjustment aggregate will hold a `workflow_request_id`. This is planned, not surprise work.
