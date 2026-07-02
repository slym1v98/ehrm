# Attendance Module

Phase 2 bounded context for raw attendance logs, calculated timesheets, adjustment requests, and monthly attendance periods. Strict DDD layering; inline adjustment approval (no Workflow BC yet).

## Aggregates

- `AttendanceRawLog` — append-only raw event with `source`, `event_type`, `event_time`, optional `geo_point`.
- `AttendanceTimesheet` — one per `(employee, work_date, period)` with minute totals and result status; `replaceWith()` supports idempotent recalculation.
- `AttendanceAdjustmentRequest` — pending → approved | rejected with inline `approved_by/approved_at`.
- `AttendancePeriod` — `open → closed → reopened` (reopen requires reason).

Domain enums: `AttendanceStatus`, `Source`, `EventType`, `AdjustmentStatus`, `PeriodStatus`.

## Calculator (`Domain/Services/AttendanceCalculator`)

Pure PHP, stateless. Handles:

- Holiday / weekend → `holiday | weekend` with `expected = 0`.
- No assignment → `absent`.
- Full-day leave → `on_leave` (worked = 0).
- Overnight shift → checkout on next calendar day attributed to start date.
- Late / early / OT respecting `overtimeRules` allowance.
- Flexitime — skips late/early (simplified; see `ponytail` comment).
- Partial leave — reduces expected minutes.

## API (all `/api/v1`, `auth:sanctum`, permission middleware)

| Method | Path | Permission |
|--------|------|------------|
| POST | `/attendance/raw-logs` | `attendance.raw-log.create` |
| GET | `/attendance/raw-logs` | `attendance.raw-log.view` |
| GET | `/attendance/timesheets` | `attendance.timesheet.view` |
| GET | `/employees/{id}/attendance` | `attendance.timesheet.view` |
| POST | `/attendance/calculate` | `attendance.timesheet.calculate` |
| POST | `/attendance-adjustment-requests` | `attendance.adjustment.create` |
| GET | `/attendance-adjustment-requests` | `attendance.adjustment.approve` |
| POST | `/attendance-adjustment-requests/{id}/approve` | `attendance.adjustment.approve` |
| POST | `/attendance-adjustment-requests/{id}/reject` | `attendance.adjustment.approve` |
| GET | `/attendance-periods` | `attendance.period.manage` |
| POST | `/attendance-periods` | `attendance.period.manage` |
| POST | `/attendance-periods/{id}/close` | `attendance.period.manage` |
| POST | `/attendance-periods/{id}/reopen` | `attendance.period.manage` |

Permissions live in `Identity/Infrastructure/Seeders/PermissionSeeder.php`; `SUPER_ADMIN` receives all, `HR_MANAGER` receives full `attendance.*`.

## Persistence

- `attendance_periods`, `attendance_raw_logs`, `attendance_timesheets`, `attendance_adjustment_requests`.
- Partial unique index on `attendance_adjustment_requests(attendance_timesheet_id) WHERE status = 'pending'` enforces one pending adjustment per timesheet; violations raise `DuplicatePendingAdjustmentException` → HTTP 409.

## YAGNI skips (intentional)

- Monthly raw-log partitioning (`ponytail`).
- Workflow BC integration for adjustment approvals (`inline` fields for now).
- Leave / Configuration read-model wiring in the calculator (empty collections until those modules expose stable query contracts).

## Tests

```bash
# Domain + calculator (19)
docker compose run --rm app php artisan test tests/Unit/Modules/Attendance --compact

# HTTP feature (7)
docker compose run --rm app php artisan test tests/Feature/Modules/Attendance --compact

# Full backend
docker compose run --rm app php artisan test --compact
```
