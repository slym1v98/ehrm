# Phase 2 Attendance Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Phase 2 Attendance module with raw logs, calculated timesheets, adjustment approvals, attendance periods, permissions, and tests.

**Architecture:** Strict DDD tactical structure under `src/backend/app/Modules/Attendance`: Domain is pure PHP, Application orchestrates commands/queries, Infrastructure owns Eloquent/HTTP/routes. `AttendanceCalculator` is a stateless domain service; adjustment approval stays inline with `approved_by`/`approved_at` and no Workflow dependency.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL 16 JSONB/UUIDs, Sanctum, Eloquent repositories, PHPUnit.

---

## File Map

- `src/backend/app/Modules/Attendance/Domain/**`: aggregates, value objects, events, exceptions, repository contracts, calculator.
- `src/backend/app/Modules/Attendance/Application/**`: commands, handlers, queries, query handlers.
- `src/backend/app/Modules/Attendance/Infrastructure/**`: Eloquent models/repositories, HTTP controllers/requests/resources.
- `src/backend/app/Modules/Attendance/Routes/api.php`: module routes under `/api/v1`.
- `src/backend/database/migrations/2026_07_02_05000*_create_attendance_*.php`: Attendance schema.
- `src/backend/app/Providers/AppServiceProvider.php`: bind Attendance repository interfaces.
- `src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php`: add `attendance.*` permissions.
- `src/backend/app/Modules/Identity/Infrastructure/Seeders/RoleSeeder.php`: grant `attendance.*` to `SUPER_ADMIN` and `HR_MANAGER`.
- `src/backend/routes/api.php`: require Attendance route file.
- `src/backend/tests/Unit/Modules/Attendance/**`: domain/application tests.
- `src/backend/tests/Feature/Modules/Attendance/**`: HTTP + authz tests.
- `src/backend/app/Modules/Attendance/README.md`: module behavior, endpoints, permissions, test commands.

---

### Task 1: Database schema

**Files:**
- Create: `src/backend/database/migrations/2026_07_02_050001_create_attendance_periods_table.php`
- Create: `src/backend/database/migrations/2026_07_02_050002_create_attendance_raw_logs_table.php`
- Create: `src/backend/database/migrations/2026_07_02_050003_create_attendance_timesheets_table.php`
- Create: `src/backend/database/migrations/2026_07_02_050004_create_attendance_adjustment_requests_table.php`

- [ ] **Step 1: Create periods migration**

Create `attendance_periods`: UUID `id`, unique `period_code`, `start_date`, `end_date`, `status` default `open`, timestamps, index on `status`.

Run: `docker compose run --rm app php artisan migrate:fresh --seed`
Expected: PASS; `attendance_periods` exists.

- [ ] **Step 2: Create raw logs migration**

Create `attendance_raw_logs`: UUID `id`, UUID `employee_id`, `source`, `event_type`, `event_time` timestamptz, nullable JSONB `geo_point`, JSONB `payload` default `{}`, `created_at`; indexes `(employee_id,event_time)` and `(source,event_time)`.

Run: `docker compose run --rm app php artisan migrate:fresh --seed`
Expected: PASS; raw log indexes exist.

- [ ] **Step 3: Create timesheets migration**

Create `attendance_timesheets`: UUID `id`, FK `attendance_period_id`, UUID `employee_id`, `work_date`, nullable UUID `shift_assignment_id`, minute int columns default `0`, `result_status`, nullable `calculation_run_id`, timestamps; unique `(employee_id,work_date,attendance_period_id)`; indexes on period and employee/date.

Run: `docker compose run --rm app php artisan migrate:fresh --seed`
Expected: PASS; unique constraint exists.

- [ ] **Step 4: Create adjustments migration**

Create `attendance_adjustment_requests`: UUID `id`, FK `attendance_timesheet_id`, UUID `employee_id`, UUID `requested_by`, `reason` text, nullable `evidence_file`, JSONB `corrections`, `status` default `pending`, nullable `approved_by`, nullable timestamptz `approved_at`, timestamps; indexes on timesheet/status; partial unique index on `attendance_timesheet_id` where `status = 'pending'`.

Run: `docker compose run --rm app php artisan migrate:fresh --seed`
Expected: PASS; duplicate pending DB guard exists.

- [ ] **Step 5: Commit schema**

```bash
git add src/backend/database/migrations/2026_07_02_050001_create_attendance_periods_table.php \
  src/backend/database/migrations/2026_07_02_050002_create_attendance_raw_logs_table.php \
  src/backend/database/migrations/2026_07_02_050003_create_attendance_timesheets_table.php \
  src/backend/database/migrations/2026_07_02_050004_create_attendance_adjustment_requests_table.php
git commit -m "feat(attendance): add schema"
```

---

### Task 2: Eloquent models

**Files:**
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Persistence/Eloquent/AttendancePeriodModel.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Persistence/Eloquent/AttendanceRawLogModel.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Persistence/Eloquent/AttendanceTimesheetModel.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Persistence/Eloquent/AttendanceAdjustmentRequestModel.php`

- [ ] **Step 1: Add AttendancePeriodModel**

Use table `attendance_periods`, string UUID key, non-incrementing, casts for `start_date`, `end_date`, timestamps.

- [ ] **Step 2: Add AttendanceRawLogModel**

Use table `attendance_raw_logs`, string UUID key, no `updated_at`, casts for `event_time`, `geo_point`, `payload`.

- [ ] **Step 3: Add AttendanceTimesheetModel**

Use table `attendance_timesheets`, string UUID key, casts for `work_date` and integer minute fields.

- [ ] **Step 4: Add AttendanceAdjustmentRequestModel**

Use table `attendance_adjustment_requests`, string UUID key, casts for `corrections` and `approved_at`.

- [ ] **Step 5: Smoke test autoload**

Run: `docker compose run --rm app php artisan test --filter=ExampleTest --compact`
Expected: PASS or no model autoload errors.

- [ ] **Step 6: Commit models**

```bash
git add src/backend/app/Modules/Attendance/Infrastructure/Persistence/Eloquent
git commit -m "feat(attendance): add eloquent models"
```

---

### Task 3: Domain value objects

**Files:**
- Create: `src/backend/app/Modules/Attendance/Domain/Aggregates/AttendanceRawLog/AttendanceRawLogId.php`
- Create: `src/backend/app/Modules/Attendance/Domain/Aggregates/AttendanceTimesheet/AttendanceTimesheetId.php`
- Create: `src/backend/app/Modules/Attendance/Domain/Aggregates/AttendanceAdjustmentRequest/AttendanceAdjustmentRequestId.php`
- Create: `src/backend/app/Modules/Attendance/Domain/Aggregates/AttendancePeriod/AttendancePeriodId.php`
- Create: `src/backend/app/Modules/Attendance/Domain/ValueObjects/GeoPoint.php`
- Create: `src/backend/app/Modules/Attendance/Domain/ValueObjects/TimeRange.php`
- Create: `src/backend/app/Modules/Attendance/Domain/ValueObjects/AttendanceStatus.php`
- Create: `src/backend/app/Modules/Attendance/Domain/ValueObjects/Source.php`
- Create: `src/backend/app/Modules/Attendance/Domain/ValueObjects/EventType.php`
- Create: `src/backend/app/Modules/Attendance/Domain/ValueObjects/AdjustmentStatus.php`
- Create: `src/backend/app/Modules/Attendance/Domain/ValueObjects/PeriodStatus.php`
- Create: `src/backend/app/Modules/Attendance/Domain/ValueObjects/TimesheetData.php`
- Test: `src/backend/tests/Unit/Modules/Attendance/Domain/ValueObjectsTest.php`

- [ ] **Step 1: Write failing VO tests**

Cover UUID round-trip, `GeoPoint` bounds, `TimeRange::durationMinutes()`, valid enum values, invalid enum values, `TimesheetData` rejects negative minutes.

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Attendance/Domain/ValueObjectsTest.php --compact`
Expected: FAIL because classes missing.

- [ ] **Step 2: Implement IDs and value objects**

Implement UUID wrapper IDs with `new()`, `fromString()`, `toString()`. Implement enums as PHP backed enums. Implement `TimesheetData` immutable readonly DTO.

- [ ] **Step 3: Run VO tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Attendance/Domain/ValueObjectsTest.php --compact`
Expected: PASS.

- [ ] **Step 4: Commit VOs**

```bash
git add src/backend/app/Modules/Attendance/Domain src/backend/tests/Unit/Modules/Attendance/Domain/ValueObjectsTest.php
git commit -m "feat(attendance): add domain value objects"
```

---

### Task 4: Domain events and exceptions

**Files:**
- Create: `src/backend/app/Modules/Attendance/Domain/Events/*.php`
- Create: `src/backend/app/Modules/Attendance/Domain/Exceptions/*.php`

- [ ] **Step 1: Add events**

Create `AttendanceRawLogRecorded`, `AttendanceCalculated`, `AttendanceAdjustmentRequested`, `AttendanceAdjustmentApproved`, `AttendanceAdjustmentRejected`, `AttendancePeriodOpened`, `AttendancePeriodClosed`, `AttendancePeriodReopened` as readonly payload classes.

- [ ] **Step 2: Add exceptions**

Create exceptions extending `App\Modules\Shared\Exceptions\AppException`: `AttendanceRawLogNotFoundException` 404, `AttendanceTimesheetNotFoundException` 404, `AttendancePeriodNotFoundException` 404, `AttendancePeriodClosedException` 422, `DuplicatePendingAdjustmentException` 409, `InvalidAttendanceAdjustmentException` 422, `InvalidAttendanceCalculationException` 422.

- [ ] **Step 3: Commit events/exceptions**

```bash
git add src/backend/app/Modules/Attendance/Domain/Events src/backend/app/Modules/Attendance/Domain/Exceptions
git commit -m "feat(attendance): add events and exceptions"
```

---

### Task 5: Domain aggregates

**Files:**
- Create: `src/backend/app/Modules/Attendance/Domain/Aggregates/AttendanceRawLog/AttendanceRawLog.php`
- Create: `src/backend/app/Modules/Attendance/Domain/Aggregates/AttendanceTimesheet/AttendanceTimesheet.php`
- Create: `src/backend/app/Modules/Attendance/Domain/Aggregates/AttendanceAdjustmentRequest/AttendanceAdjustmentRequest.php`
- Create: `src/backend/app/Modules/Attendance/Domain/Aggregates/AttendancePeriod/AttendancePeriod.php`
- Test: `src/backend/tests/Unit/Modules/Attendance/Domain/AttendanceAggregatesTest.php`

- [ ] **Step 1: Write failing aggregate tests**

Cover raw log recording emits event, timesheet recalculation replaces values, duplicate approval/rejection transition invalid, closed period blocks guard consumer via `isClosed()`, reopen requires non-empty reason and emits event.

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Attendance/Domain/AttendanceAggregatesTest.php --compact`
Expected: FAIL.

- [ ] **Step 2: Implement AttendanceRawLog**

Implement `record()`, getters, `releaseEvents()`, `reconstitute()`; no update/delete methods.

- [ ] **Step 3: Implement AttendanceTimesheet**

Implement `fromCalculation()`, `replaceWith()`, getters, `releaseEvents()`, `reconstitute()`.

- [ ] **Step 4: Implement AttendanceAdjustmentRequest**

Implement `submit()`, `approve()`, `reject()`, status transition guards, getters, `releaseEvents()`, `reconstitute()`.

- [ ] **Step 5: Implement AttendancePeriod**

Implement `open()`, `close()`, `reopen()`, `isClosed()`, date range guard, getters, `releaseEvents()`, `reconstitute()`.

- [ ] **Step 6: Run aggregate tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Attendance/Domain/AttendanceAggregatesTest.php --compact`
Expected: PASS.

- [ ] **Step 7: Commit aggregates**

```bash
git add src/backend/app/Modules/Attendance/Domain/Aggregates src/backend/tests/Unit/Modules/Attendance/Domain/AttendanceAggregatesTest.php
git commit -m "feat(attendance): add aggregates"
```

---

### Task 6: Attendance calculator

**Files:**
- Create: `src/backend/app/Modules/Attendance/Domain/Services/AttendanceCalculator.php`
- Test: `src/backend/tests/Unit/Modules/Attendance/Domain/AttendanceCalculatorTest.php`

- [ ] **Step 1: Write failing calculator tests**

Cover overnight 22:00–06:00 = 480 worked, late arrival = 30 late, early leave = 30 early, overtime = 60, flexitime skips late/early when min hours met, no logs + assignment = absent, full-day leave = on_leave, weekend/holiday expected = 0, partial AM leave reduces expected minutes.

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Attendance/Domain/AttendanceCalculatorTest.php --compact`
Expected: FAIL.

- [ ] **Step 2: Implement minimal read DTOs inside test if needed**

Use anonymous objects/stdClass in tests for shift assignment/template, leave windows, holidays; do not introduce Shift/Leave dependencies into Domain.

- [ ] **Step 3: Implement calculator**

Implement `calculate($employeeId, CarbonImmutable $workDate, array $rawLogs, ?object $assignment, array $leaves, array $holidays): TimesheetData`. Sort logs by `eventTime`, pair check-in/out, attribute overnight checkout to start date, clamp negative minutes to zero. Add comment: `ponytail: Flexitime simplified — no core-hours enforcement; add when Phase 4 rules engine exists.`

- [ ] **Step 4: Run calculator tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Attendance/Domain/AttendanceCalculatorTest.php --compact`
Expected: PASS.

- [ ] **Step 5: Commit calculator**

```bash
git add src/backend/app/Modules/Attendance/Domain/Services/AttendanceCalculator.php src/backend/tests/Unit/Modules/Attendance/Domain/AttendanceCalculatorTest.php
git commit -m "feat(attendance): add calculator"
```

---

### Task 7: Repository contracts and persistence

**Files:**
- Create: `src/backend/app/Modules/Attendance/Domain/Repositories/AttendanceRawLogRepositoryInterface.php`
- Create: `src/backend/app/Modules/Attendance/Domain/Repositories/AttendanceTimesheetRepositoryInterface.php`
- Create: `src/backend/app/Modules/Attendance/Domain/Repositories/AttendanceAdjustmentRequestRepositoryInterface.php`
- Create: `src/backend/app/Modules/Attendance/Domain/Repositories/AttendancePeriodRepositoryInterface.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Persistence/Repositories/EloquentAttendanceRawLogRepository.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Persistence/Repositories/EloquentAttendanceTimesheetRepository.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Persistence/Repositories/EloquentAttendanceAdjustmentRequestRepository.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Persistence/Repositories/EloquentAttendancePeriodRepository.php`
- Modify: `src/backend/app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Define interfaces**

Raw logs: `saveAndDispatch`, `findPaginated`, `findByEmployeeAndRange`. Timesheets: `findById`, `findByEmployeeDatePeriod`, `saveAndDispatch`, `findPaginated`, `findByEmployeeAndRange`. Adjustments: `findById`, `hasPendingForTimesheet`, `saveAndDispatch`, `findPendingPaginated`. Periods: `findById`, `findByCode`, `findOpenForDate`, `saveAndDispatch`, `findPaginated`.

- [ ] **Step 2: Implement Eloquent repositories**

Map models ↔ aggregates, preserve JSON arrays, dispatch `releaseEvents()` after persistence. Catch duplicate pending DB error and throw `DuplicatePendingAdjustmentException`.

- [ ] **Step 3: Bind interfaces**

Bind all four interfaces to Eloquent repositories in `AppServiceProvider::register()`.

- [ ] **Step 4: Commit persistence**

```bash
git add src/backend/app/Modules/Attendance/Domain/Repositories src/backend/app/Modules/Attendance/Infrastructure/Persistence src/backend/app/Providers/AppServiceProvider.php
git commit -m "feat(attendance): add repositories"
```

---

### Task 8: Application layer

**Files:**
- Create: `src/backend/app/Modules/Attendance/Application/Commands/**`
- Create: `src/backend/app/Modules/Attendance/Application/CommandHandlers/**`
- Create: `src/backend/app/Modules/Attendance/Application/Queries/**`
- Create: `src/backend/app/Modules/Attendance/Application/QueryHandlers/**`
- Test: `src/backend/tests/Unit/Modules/Attendance/Application/AttendanceHandlersTest.php`

- [ ] **Step 1: Write failing handler tests**

Cover record log blocks closed period, submit adjustment blocks closed period, duplicate pending adjustment throws 409 exception, approve triggers timesheet recalculation, period reopen requires reason.

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Attendance/Application/AttendanceHandlersTest.php --compact`
Expected: FAIL.

- [ ] **Step 2: Implement raw log command/handler**

Create `RecordAttendanceRawLogCommand` and handler. Handler checks `AttendancePeriodRepositoryInterface::findOpenForDate()`/closed date guard before saving.

- [ ] **Step 3: Implement timesheet commands/handlers**

Create `CalculateAttendanceForPeriodCommand`, `RecalculateAttendanceForEmployeeCommand` and handlers. Use calculator, raw log repo, timesheet repo. Use empty leaves/holidays initially; `ponytail: Leave/holiday real read-model wiring deferred until those modules expose stable query contracts.`

- [ ] **Step 4: Implement adjustment commands/handlers**

Create submit/approve/reject commands and handlers. Submit checks timesheet exists, period not closed, no pending duplicate. Approve saves approval and recalculates target timesheet.

- [ ] **Step 5: Implement period commands/handlers**

Create open/close/reopen commands and handlers. Reopen persists reason via event payload only.

- [ ] **Step 6: Implement query objects/handlers**

Create list/get query handlers for timesheets, employee attendance, raw logs, pending adjustments, periods.

- [ ] **Step 7: Run handler tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Attendance/Application/AttendanceHandlersTest.php --compact`
Expected: PASS.

- [ ] **Step 8: Commit application layer**

```bash
git add src/backend/app/Modules/Attendance/Application src/backend/tests/Unit/Modules/Attendance/Application/AttendanceHandlersTest.php
git commit -m "feat(attendance): add application layer"
```

---

### Task 9: HTTP API and routes

**Files:**
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Controllers/AttendanceRawLogController.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Controllers/AttendanceTimesheetController.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Controllers/AttendanceAdjustmentController.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Controllers/AttendancePeriodController.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Requests/RecordAttendanceRawLogRequest.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Requests/CalculateAttendanceRequest.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Requests/SubmitAttendanceAdjustmentRequest.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Requests/OpenAttendancePeriodRequest.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Requests/ReopenAttendancePeriodRequest.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Resources/AttendanceRawLogResource.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Resources/AttendanceTimesheetResource.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Resources/AttendanceAdjustmentRequestResource.php`
- Create: `src/backend/app/Modules/Attendance/Infrastructure/Http/Resources/AttendancePeriodResource.php`
- Create: `src/backend/app/Modules/Attendance/Routes/api.php`
- Modify: `src/backend/routes/api.php`

- [ ] **Step 1: Add FormRequests**

Validate request shape: UUIDs, enum strings, ISO datetimes/dates, `geo_point.lat` between -90/90, `geo_point.lng` between -180/180, reopen `reason` required string.

- [ ] **Step 2: Add resources**

Return stable `{data: ...}` compatible shapes for raw logs, timesheets, adjustments, periods.

- [ ] **Step 3: Add controllers**

Controllers call command/query handlers only; no domain logic in controllers.

- [ ] **Step 4: Add module routes**

Wire under `Route::prefix('v1')->middleware('auth:sanctum')`: `/attendance/raw-logs`, `/attendance/timesheets`, `/employees/{id}/attendance`, `/attendance/calculate`, `/attendance-adjustment-requests`, approve/reject endpoints, `/attendance-periods`, close/reopen endpoints with permission middleware from spec.

- [ ] **Step 5: Register route file**

Add `require __DIR__ . '/../app/Modules/Attendance/Routes/api.php';` to `src/backend/routes/api.php`.

- [ ] **Step 6: Route smoke test**

Run: `docker compose run --rm app php artisan route:list | grep attendance`
Expected: all Attendance routes visible.

- [ ] **Step 7: Commit HTTP**

```bash
git add src/backend/app/Modules/Attendance/Infrastructure/Http src/backend/app/Modules/Attendance/Routes src/backend/routes/api.php
git commit -m "feat(attendance): add HTTP API"
```

---

### Task 10: Permissions and feature tests

**Files:**
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php`
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/RoleSeeder.php`
- Create: `src/backend/tests/Feature/Modules/Attendance/AttendanceApiTest.php`

- [ ] **Step 1: Add attendance permissions**

Add `attendance.raw-log.create`, `attendance.raw-log.view`, `attendance.timesheet.view`, `attendance.timesheet.calculate`, `attendance.adjustment.create`, `attendance.adjustment.approve`, `attendance.period.manage` to `PermissionSeeder`.

- [ ] **Step 2: Grant HR roles**

Add all `attendance.*` permissions to `HR_MANAGER`; `SUPER_ADMIN` already gets all permissions via `all`.

- [ ] **Step 3: Write feature tests**

Cover unauthenticated request returns 401, missing permission returns 403, raw log create/list works, calculate creates timesheet, adjustment submit duplicate returns 409, close period blocks raw log/adjustment with 422, reopen requires reason, approve adjustment returns approved status.

- [ ] **Step 4: Run feature tests**

Run: `docker compose run --rm app php artisan test tests/Feature/Modules/Attendance/AttendanceApiTest.php --compact`
Expected: PASS.

- [ ] **Step 5: Commit permissions/tests**

```bash
git add src/backend/app/Modules/Identity/Infrastructure/Seeders src/backend/tests/Feature/Modules/Attendance/AttendanceApiTest.php
git commit -m "test(attendance): add API coverage and permissions"
```

---

### Task 11: README and final verification

**Files:**
- Create: `src/backend/app/Modules/Attendance/README.md`

- [ ] **Step 1: Write README**

Document aggregates, calculator rules, endpoints, permissions, YAGNI skips, test commands.

- [ ] **Step 2: Run targeted tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Attendance tests/Feature/Modules/Attendance --compact`
Expected: PASS.

- [ ] **Step 3: Run full backend tests**

Run: `docker compose run --rm app php artisan test --compact`
Expected: PASS.

- [ ] **Step 4: Check working tree**

Run: `git status --short`
Expected: only intentional files before commit; clean after commit.

- [ ] **Step 5: Commit docs**

```bash
git add src/backend/app/Modules/Attendance/README.md
git commit -m "docs(attendance): add module README"
```

---

## Execution Notes

- Execute in isolated worktree `.worktrees/attendance` on branch `feature/attendance`.
- Keep commits small in the task order above.
- Do not add Workflow BC integration; inline approval is intentional.
- Do not add raw-log partitioning; add only when data volume proves need.
- Do not add Leave dependency yet; calculator accepts empty leave windows until Leave BC exists.
- End workflow: push branch and create PR; do not merge locally into `main`.

## Self-Review

- Spec coverage: schema, aggregates, calculator, periods, adjustments, permissions, routes, tests, README covered.
- Placeholder scan: no TBD/TODO/implement-later placeholders.
- Type consistency: names match spec paths and API routes; repository/interface names match handler references.
