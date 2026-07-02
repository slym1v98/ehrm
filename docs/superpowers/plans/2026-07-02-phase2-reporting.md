# Phase 2 Reporting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Phase 2 Reporting module with configurable definitions, async runs, 5 seeded queries, and API.

**Architecture:** Thin read-model DDD module. `ExecuteReportHandler` persists `ReportRun` and dispatches queued job. Job resolves `ReportQueryInterface` implementation from registry and stores result snapshot.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL 16 JSONB/UUIDs, Sanctum, Eloquent repositories, PHPUnit.

---

## File Map

- `src/backend/app/Modules/Reporting/Domain/**`: aggregates, VOs, exceptions, repository contracts.
- `src/backend/app/Modules/Reporting/Application/**`: command/handler, query contract, registry.
- `src/backend/app/Modules/Reporting/Infrastructure/**`: Eloquent models/repositories, HTTP, seeders.
- `src/backend/app/Modules/Reporting/Routes/api.php`: module routes.
- `src/backend/database/migrations/2026_07_02_12000*_create_reporting_*.php`: schema.
- `src/backend/app/Providers/AppServiceProvider.php`: bindings.
- `src/backend/routes/api.php`: require route file.
- `src/backend/tests/Unit/Modules/Reporting/**`: domain/application tests.
- `src/backend/tests/Feature/Modules/Reporting/**`: HTTP tests.

---

### Task 1: Schema

**Files:**
- Create: `src/backend/database/migrations/2026_07_02_120001_create_report_definitions_table.php`
- Create: `src/backend/database/migrations/2026_07_02_120002_create_report_runs_table.php`

- [ ] **Step 1: definitions migration**

UUID `id`, unique `code`, `name`, nullable `description`, `query_class`, JSONB `filters_schema` default `[]`, JSONB `columns_schema` default `[]`, boolean `is_active` default true, timestamps.

`docker compose run --rm app php artisan migrate:fresh --seed` — PASS.

- [ ] **Step 2: runs migration**

UUID `id`, FK `report_definition_id` to `report_definitions` cascade delete, UUID `requested_by` FK to users, JSONB `filters`, `status`, nullable JSONB `result`, nullable text `error`, nullable `started_at`, nullable `completed_at`, timestamps. Indexes on `requested_by`, `status`, `(report_definition_id, created_at)`.

- [ ] **Step 3: Commit**

```bash
git add src/backend/database/migrations/2026_07_02_12000*
git commit -m "feat(reporting): add schema"
```

---

### Task 2: Domain

**Files:**
- Create IDs, VOs, aggregates, exceptions, repos interfaces.

- [ ] **Step 1: IDs, VO, exceptions, repos interfaces**

Write `ReportDefinitionId`, `ReportRunId`, `ReportRunStatus` (enum: requested|running|completed|failed). `ReportDefinition` with create/activate/deactivate. `ReportRun` with request/start/complete/fail + status transition guards. Exceptions extend `AppException`. Repositories as interfaces.

- [ ] **Step 2: Commit domain**

---

### Task 3: Persistence

- `ReportDefinitionModel`, `ReportRunModel`, 2 Eloquent repositories with `toDomain`/`fromDomain`.

- [ ] **Step 1: Models**
- [ ] **Step 2: Repositories + AppServiceProvider bindings**
- [ ] **Step 3: Commit**

---

### Task 4: Application + HTTP**

**Files:**
- `ReportQueryInterface` (Application/Contracts/)
- `ExecuteReportCommand`, `ExecuteReportHandler` (creates run, dispatches job)
- `ReportQueryRegistry` (Infrastructure/Console/)
- `ReportRunJob` (Infrastructure/Jobs/ — classic Laravel queued job)
- `ReportController` with indexDefinitions, createRun, listRuns, getRun
- Resources, routes, route loader

- [ ] **Step 1: All application + HTTP files**
- [ ] **Step 2: Feature tests + commit**

---

### Task 5: Seeders + integration**

- `ReportingPermissionSeeder` (inline in PermissionSeeder)
- `ReportingDefinitionSeeder` (call from DatabaseSeeder)
- Role grants in `RoleSeeder`

- [ ] **Step 1: Seed permissions + definitions**
- [ ] **Step 2: Role grants**
- [ ] **Step 3: Verify seed**
- [ ] **Step 4: Commit**

---

### Task 6: Final verification

- [ ] **Step 1: Targeted tests**
- [ ] **Step 2: Full suite**
- [ ] **Step 3: Spec acceptance checklist**

