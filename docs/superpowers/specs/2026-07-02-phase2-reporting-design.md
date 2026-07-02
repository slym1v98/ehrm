# Phase 2 Reporting BC Design

Version: 0.1
Date: 2026-07-02
Status: Design approved (brainstorming)

## 1. Scope

Build the Reporting module (`app/Modules/Reporting/`) as the final Phase 2 sub-project. Covers configurable report definitions and async report runs using registered query classes. Provides 5 seeded reports across Attendance, Leave, Payroll, Workflow, and Notification modules.

**In scope:** `ReportDefinition` CRUD + activate/deactivate, `ReportRun` with state machine (requested → running → completed/failed), `ReportQueryInterface` for each report, async execution via Laravel queued job, 5 seeded definitions (attendance.summary, leave.balance, payroll.summary, workflow.pending, notification.delivery), permission integration with Identity module, full test suite.

**Out of scope:** Frontend report builder/designer, data export to CSV/PDF/XLSX, chart/dashboard rendering, BI-as-a-service, scheduled/digest reporting, drill-down beyond result snapshot, live WebSocket/SSE progress, large-result pagination (results stored as JSONB for Phase 2), Multi-tenant data isolation beyond existing data-scope rules.

## 2. Architecture

Strict DDD tactical pattern with 3 layers, mirroring all existing Phase 2 modules.

```
Module/Reporting/
  Domain/         — aggregates, value objects, exceptions, repository contracts
  Application/    — commands, handlers, queries, query handlers, report query contracts
  Infrastructure/ — Eloquent, HTTP controllers, console registry, seeders, routes
```

**Key architectural decisions:**

- **Read-model only:** Reporting composes SQL queries across other BCs but never mutates shared tables. No event subscriptions, no outbox, no channel adapters.
- **Async via Laravel queue:** `ExecuteReportHandler` persists `ReportRun(status=running)` then dispatches `ReportRunJob`. Job runs `ReportQueryInterface::execute()`, updates status + result/error. No custom worker needed — reuse Laravel `QUEUE_CONNECTION=database` or `redis`.
- **ReportQueryInterface per report:** Each seeded report definition maps to a class implementing `ReportQueryInterface { execute(params, user): array }`. Registry in `ReportQueryRegistry` resolves class by code.
- **Extensible:** Adding a new report = 1 DB row + 1 query class file + 1 registry binding. No controller/changes.
- **Data visibility:** Phase 2 uses simple rule: user can see own runs, or all runs if `report.run.view-all` permission. Report queries filter by input params, not implicit scope. Implementation of department-scoped visibility deferred.

## 3. Module Layout

```
app/Modules/Reporting/
  Domain/
    Aggregates/
      ReportDefinition/ReportDefinition.php, ReportDefinitionId.php
      ReportRun/ReportRun.php, ReportRunId.php
    ValueObjects/
      ReportRunStatus.php
    Repositories/
      ReportDefinitionRepositoryInterface.php
      ReportRunRepositoryInterface.php
    Exceptions/
      ReportDefinitionNotFoundException.php
      ReportRunNotFoundException.php
  Application/
    Commands/
      ExecuteReportCommand.php
    CommandHandlers/
      ExecuteReportHandler.php
    Contracts/
      ReportQueryInterface.php
    Queries/
      ListReportDefinitionsQuery.php
      ListReportRunsQuery.php
      GetReportRunQuery.php
  Infrastructure/
    Persistence/
      Eloquent/
        ReportDefinitionModel.php
        ReportRunModel.php
      Repositories/
        EloquentReportDefinitionRepository.php
        EloquentReportRunRepository.php
    Http/
      Controllers/
        ReportController.php
      Resources/
        ReportDefinitionResource.php
        ReportRunResource.php
    Console/
      ReportQueryRegistry.php
    Seeders/
      ReportingPermissionSeeder.php
      ReportingDefinitionSeeder.php
  Routes/api.php
```

## 4. Schema

### `report_definitions` (migration `2026_07_02_120001`)

| Column | Type | Notes |
|---|---|---|
| id | uuid | PK |
| code | varchar(100) | unique, e.g. `attendance.summary` |
| name | varchar(255) | |
| description | text | Nullable |
| query_class | varchar(255) | FQCN implementing ReportQueryInterface |
| filters_schema | jsonb | Array of `{key, label, type, required}` |
| columns_schema | jsonb | Array of `{key, label, type}` |
| is_active | boolean | Default true |
| timestamps | | |

### `report_runs` (migration `2026_07_02_120002`)

| Column | Type | Notes |
|---|---|---|
| id | uuid | PK |
| report_definition_id | uuid | FK to report_definitions |
| requested_by | uuid | FK to users |
| filters | jsonb | Applied filter values |
| status | varchar(20) | requested / running / completed / failed |
| result | jsonb | Nullable — completed snapshot |
| error | text | Nullable — failure detail |
| started_at | timestamptz | Nullable |
| completed_at | timestamptz | Nullable |
| timestamps | | |

Indexes: `(requested_by)`, `(status)`, `(report_definition_id, created_at)`.

## 5. Domain Model

### 5.1 ReportDefinition

```
ReportDefinition {
  id: ReportDefinitionId (UUID VO)
  code: string (unique)
  name: string
  description: ?string
  queryClass: string
  filtersSchema: array
  columnsSchema: array
  active: bool

  static create(code, name, description, queryClass, filters, columns, active): self
  activate(): void
  deactivate(): void

  Invariants:
  - Code must not change after creation (immutable domain key).
  - Inactive definitions cannot be run.
}
```

### 5.2 ReportRun

```
ReportRun {
  id: ReportRunId (UUID VO)
  reportDefinitionId: string
  requestedBy: string
  filters: array
  status: ReportRunStatus (VO)
  result: ?array
  error: ?string
  startedAt: ?CarbonImmutable
  completedAt: ?CarbonImmutable

  static request(reportDefinitionId, requestedBy, filters): self
  start(at): void
  complete(result, at): void
  fail(error, at): void

  Invariants:
  - Status transitions: requested → running → completed|failed.
  - completed cannot transition to failed (or vice versa).
  - result can only be set once (on complete).
  - error can only be set once (on fail).
}
```

## 6. Report Query Contract

```php
interface ReportQueryInterface {
    /** @return array of associative arrays */
    public function execute(array $filters, string $requestedBy): array;
}
```

Each implementation is a plain query class. Reads from DB tables directly (Eloquen models or DB facade with raw SQL). Returns array of rows. No cross-module aggregate hydration.

The `ReportQueryRegistry` resolves `query_class` string from `ReportDefinition` to the concrete class via Laravel's container, allowing DI.

## 7. Seeded Reports

| Code | Filters | Output columns |
|---|---|---|
| `attendance.summary` | period_id (required), department_id (optional), employee_id (optional) | employee_id, employee_name, work_days, present_days, absent_days, late_minutes, overtime_minutes |
| `leave.balance` | year (required, default current), department_id (optional), employee_id (optional) | employee_id, employee_name, leave_type, opening, accrued, used, carry_over, remaining |
| `payroll.summary` | payroll_period_id (required), department_id (optional), employee_id (optional) | employee_id, employee_name, component_type, amount, net_pay, status |
| `workflow.pending` | assignee_user_id (optional), subject_type (optional) | request_id, subject_type, subject_id, current_step, status, started_at, deadline |
| `notification.delivery` | from (required date), to (required date), channel (optional), status (optional) | channel, total, sent, failed, pending, success_rate |

## 8. API Endpoints

All under `/api/v1`, Sanctum auth.

| Method | Path | Permission |
|---|---|---|
| GET | `/reports` | report.definition.view |
| POST | `/reports/{code}/runs` | report.run.create |
| GET | `/report-runs` | report.run.view-own (or view-all with permission) |
| GET | `/report-runs/{id}` | report.run.view-own (or view-all with permission) |

## 9. Permissions

Seeded via `ReportingPermissionSeeder`:

| Code | Description |
|---|---|
| report.definition.view | View report definitions |
| report.run.create | Create report runs |
| report.run.view-own | View own report runs |
| report.run.view-all | View all report runs (admin/HR) |

Default role grants:
- Admin: all 4
- HR Manager: all 4
- HR Staff: definition.view, run.create, run.view-own
- Department Manager: definition.view, run.create, run.view-own
- Employee: none (reporting is management-only)

## 10. Error Handling

| Scenario | Behavior |
|---|---|
| Report code not found | 404 |
| Report definition inactive | 422 with message |
| Invalid filters | Request validation 422 |
| Query class not found in registry | Run created, job marks it failed |
| Query execution throws | Run marked failed with exception message |
| User without view-all accesses other run | 404 (not 403 — don't reveal existence) |

## 11. Async Job

`ExecuteReportHandler` dispatches `ReportRunJob` via `dispatch()->onQueue('reports')`. The job uses `ShouldQueue` / `SerializesModels`. On handle:
1. Load report run + definition.
2. Mark run status=running, set started_at.
3. Resolve query class from registry.
4. Call execute(filters, requested_by).
5. On success: update result, status=completed, completed_at.
6. On failure: update error, status=failed, completed_at.

No retry by default — if job fails, user sees "failed" and can request again. Retry configurable up to 1 attempt.

## 12. Testing

### Domain Unit Tests (`tests/Unit/Modules/Reporting/Domain/`)
- ReportDefinition: create, activate, deactivate
- ReportRun: status transitions, guard invalid transitions, result/error set-once

### Application Unit Tests (`tests/Unit/Modules/Reporting/Application/`)
- ExecuteReportHandler: creates run and dispatches job
- ReportQueryRegistry: resolves class correctly, throws on missing

### Feature Tests (`tests/Feature/Modules/Reporting/`)
- Unauthenticated returns 401
- List definitions returns seeded definitions
- Create run creates and returns run id
- Cannot create run for inactive definition
- User without view-all cannot see other's run
- Admin with view-all can see any run
- Run detail shows status before completion

## 13. Acceptance Criteria

1. ✅ Reporting BC follows Phase 2 DDD layout conventions.
2. ✅ ReportDefinition + ReportRun aggregates defined with invariants.
3. ✅ 5 seeded definitions cover Attendance, Leave, Payroll, Workflow, Notification.
4. ✅ Async via Laravel queued job.
5. ✅ ReportQueryInterface contract for all query classes.
6. ✅ API endpoints for definition list, run create, run list, run detail.
7. ✅ Permissions seeded with role defaults.
8. ✅ Invalid transition guards on ReportRun.
9. ✅ Domain, application, feature tests exist with auth-boundary coverage.
