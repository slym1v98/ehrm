# Phase 2 Shift BC Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Phase 2 Shift bounded context with shift templates, assignments, overtime/flexibility rules, and APIs that Attendance can consume later.

**Architecture:** Strict DDD tactical structure under `src/backend/app/Modules/Shift`: Domain is pure PHP, Application orchestrates commands/queries, Infrastructure owns Eloquent/HTTP/routes/seeders. Two aggregate roots: `ShiftTemplate` and `ShiftAssignment`; JSON rules stay in value objects for Phase 2.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL 16, UUID primary keys, Sanctum, Eloquent repositories, PHPUnit.

---

## File Map

- `src/backend/app/Modules/Shift/Domain/**`: value objects, aggregates, events, exceptions, repository contracts.
- `src/backend/app/Modules/Shift/Application/**`: commands, handlers, queries, query handlers.
- `src/backend/app/Modules/Shift/Infrastructure/**`: Eloquent models/repositories, HTTP controllers/requests/resources, routes, seeders.
- `src/backend/database/migrations/2026_07_02_04000*_create_shift_*.php`: Shift tables.
- `src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php`: add `shift.*` permissions.
- `src/backend/app/Modules/Identity/Infrastructure/Seeders/RoleSeeder.php`: grant `shift.*` to `SUPER_ADMIN` and `HR_MANAGER`.
- `src/backend/routes/api.php`: load Shift routes.
- `src/backend/tests/Unit/Modules/Shift/**`: domain/application tests.
- `src/backend/tests/Feature/Modules/Shift/**`: API tests.

---

### Task 1: Database migrations

**Files:**
- Create: `src/backend/database/migrations/2026_07_02_040001_create_shift_templates_table.php`
- Create: `src/backend/database/migrations/2026_07_02_040002_create_shift_assignments_table.php`

- [ ] **Step 1: Create shift_templates migration**

Create columns: `id`, `code`, `name`, `start_time`, `end_time`, `is_overnight`, `break_minutes`, `late_tolerance_minutes`, `overtime_rules` JSONB, `flexibility_rules` JSONB, `payroll_attribution_rule`, `active`, timestamps.

Run: `docker compose run --rm app php artisan migrate:fresh --seed`
Expected: migration succeeds.

- [ ] **Step 2: Create shift_assignments migration**

Create columns: `id`, `shift_template_id`, `assignable_type`, `assignable_id`, `effective_from`, `effective_to`, `recurrence_rule` JSONB, `active`, timestamps, indexes on `(assignable_type, assignable_id)` and date fields.

Run: `docker compose run --rm app php artisan migrate:fresh --seed`
Expected: migration succeeds.

- [ ] **Step 3: Commit schema**

```bash
git add src/backend/database/migrations/2026_07_02_040001_create_shift_templates_table.php \
  src/backend/database/migrations/2026_07_02_040002_create_shift_assignments_table.php
git commit -m "feat(shift): add shift schema"
```

---

### Task 2: Eloquent models

**Files:**
- Create: `src/backend/app/Modules/Shift/Infrastructure/Persistence/Eloquent/ShiftTemplateModel.php`
- Create: `src/backend/app/Modules/Shift/Infrastructure/Persistence/Eloquent/ShiftAssignmentModel.php`

- [ ] **Step 1: Add ShiftTemplateModel**

Use UUID string key, casts for booleans and arrays/JSON.

- [ ] **Step 2: Add ShiftAssignmentModel**

Use UUID string key, casts for `effective_from`, `effective_to`, `recurrence_rule`, `active`.

- [ ] **Step 3: Smoke test autoload**

Run: `docker compose run --rm app php artisan test --filter=ExampleTest --compact`
Expected: PASS or no model autoload errors.

- [ ] **Step 4: Commit models**

```bash
git add src/backend/app/Modules/Shift/Infrastructure/Persistence/Eloquent
git commit -m "feat(shift): add eloquent models"
```

---

### Task 3: Domain value objects and enums

**Files:**
- Create: `src/backend/app/Modules/Shift/Domain/Aggregates/ShiftTemplate/*.php`
- Create: `src/backend/app/Modules/Shift/Domain/Aggregates/ShiftAssignment/*.php`
- Test: `src/backend/tests/Unit/Modules/Shift/Domain/*Test.php`

- [ ] **Step 1: Write failing VO tests**

Cover `ShiftWindow::duration()`, overnight detection, negative overtime/flexibility values rejected, invalid recurrence frequency rejected.

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Shift/Domain --compact`
Expected: FAIL because classes missing.

- [ ] **Step 2: Implement VOs**

Create `ShiftTemplateId`, `ShiftAssignmentId`, `ShiftWindow`, `OvertimeRules`, `FlexibilityRules`, `RecurrenceRule`.

- [ ] **Step 3: Run VO tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Shift/Domain --compact`
Expected: PASS.

- [ ] **Step 4: Commit VOs**

```bash
git add src/backend/app/Modules/Shift/Domain/Aggregates src/backend/tests/Unit/Modules/Shift/Domain
git commit -m "feat(shift): add domain value objects"
```

---

### Task 4: Domain events and exceptions

**Files:**
- Create: `src/backend/app/Modules/Shift/Domain/Events/*.php`
- Create: `src/backend/app/Modules/Shift/Domain/Exceptions/*.php`

- [ ] **Step 1: Add events**

Create `ShiftTemplateCreated`, `ShiftTemplateUpdated`, `ShiftTemplateActivated`, `ShiftTemplateDeactivated`, `ShiftAssigned`, `ShiftAssignmentEnded`, `ShiftAssignmentChanged`.

- [ ] **Step 2: Add exceptions**

Create `ShiftTemplateNotFoundException` (404), `DuplicateShiftTemplateCodeException` (409), `InvalidShiftTemplateException` (422), `ShiftAssignmentNotFoundException` (404), `OverlappingShiftAssignmentException` (422), all extending `AppException`.

- [ ] **Step 3: Commit events/exceptions**

```bash
git add src/backend/app/Modules/Shift/Domain/Events src/backend/app/Modules/Shift/Domain/Exceptions
git commit -m "feat(shift): add domain events and exceptions"
```

---

### Task 5: Domain aggregates

**Files:**
- Create: `src/backend/app/Modules/Shift/Domain/Aggregates/ShiftTemplate/ShiftTemplate.php`
- Create: `src/backend/app/Modules/Shift/Domain/Aggregates/ShiftAssignment/ShiftAssignment.php`
- Test: `src/backend/tests/Unit/Modules/Shift/Domain/ShiftTemplateTest.php`
- Test: `src/backend/tests/Unit/Modules/Shift/Domain/ShiftAssignmentTest.php`

- [ ] **Step 1: Write failing aggregate tests**

Cover overnight template requires payroll attribution, deactivated template cannot assign, overlapping assignment throws, end assignment sets inactive/date.

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Shift/Domain --compact`
Expected: FAIL.

- [ ] **Step 2: Implement ShiftTemplate aggregate**

Implement `create`, `updateDetails`, `activate`, `deactivate`, `releaseEvents`, `reconstitute`.

- [ ] **Step 3: Implement ShiftAssignment aggregate**

Implement `assign`, `endAssignment`, `changeTemplate`, `releaseEvents`, `reconstitute`.

- [ ] **Step 4: Run aggregate tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Shift/Domain --compact`
Expected: PASS.

- [ ] **Step 5: Commit aggregates**

```bash
git add src/backend/app/Modules/Shift/Domain/Aggregates src/backend/tests/Unit/Modules/Shift/Domain
git commit -m "feat(shift): add domain aggregates"
```

---

### Task 6: Repository contracts and persistence

**Files:**
- Create: `src/backend/app/Modules/Shift/Domain/Repositories/ShiftTemplateRepositoryInterface.php`
- Create: `src/backend/app/Modules/Shift/Domain/Repositories/ShiftAssignmentRepositoryInterface.php`
- Create: `src/backend/app/Modules/Shift/Infrastructure/Persistence/Repositories/EloquentShiftTemplateRepository.php`
- Create: `src/backend/app/Modules/Shift/Infrastructure/Persistence/Repositories/EloquentShiftAssignmentRepository.php`
- Modify: `src/backend/app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Define interfaces**

Template methods: `findById`, `findByCode`, `existsByCode`, `findAllPaginated`, `saveAndDispatch`.
Assignment methods: `findById`, `findByEmployeeId`, `findByDepartmentId`, `findActiveByEntity`, `findAllPaginated`, `saveAndDispatch`.

- [ ] **Step 2: Implement Eloquent repositories**

Map JSONB columns to VOs, dispatch events after save.

- [ ] **Step 3: Bind interfaces**

Bind both interfaces in `AppServiceProvider`.

- [ ] **Step 4: Commit persistence**

```bash
git add src/backend/app/Modules/Shift/Domain/Repositories src/backend/app/Modules/Shift/Infrastructure/Persistence src/backend/app/Providers/AppServiceProvider.php
git commit -m "feat(shift): add repositories"
```

---

### Task 7: Application layer

**Files:**
- Create: `src/backend/app/Modules/Shift/Application/**`
- Test: `src/backend/tests/Unit/Modules/Shift/Application/**`

- [ ] **Step 1: Write failing handler tests**

Cover duplicate template code, inactive template assignment, overlap guard, not founds.

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Shift/Application --compact`
Expected: FAIL.

- [ ] **Step 2: Implement ShiftTemplate commands/handlers**

Create/update/activate/deactivate handlers with permission checks.

- [ ] **Step 3: Implement ShiftAssignment commands/handlers**

Assign/end/change handlers with overlap checks.

- [ ] **Step 4: Implement queries/query handlers**

List/show templates, employee shifts, department shifts.

- [ ] **Step 5: Run app tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Shift/Application --compact`
Expected: PASS.

- [ ] **Step 6: Commit application layer**

```bash
git add src/backend/app/Modules/Shift/Application src/backend/tests/Unit/Modules/Shift/Application
git commit -m "feat(shift): add application layer"
```

---

### Task 8: HTTP layer and routes

**Files:**
- Create: `src/backend/app/Modules/Shift/Infrastructure/Http/Controllers/*.php`
- Create: `src/backend/app/Modules/Shift/Infrastructure/Http/Requests/*.php`
- Create: `src/backend/app/Modules/Shift/Infrastructure/Http/Resources/*.php`
- Create: `src/backend/app/Modules/Shift/Routes/api.php`
- Modify: `src/backend/routes/api.php`

- [ ] **Step 1: Add FormRequests**

Validate shape only; domain conflict remains in handlers.

- [ ] **Step 2: Add resources/controllers**

Stable JSON for templates and assignments.

- [ ] **Step 3: Add routes**

Wire all Shift endpoints under `/api/v1`.

- [ ] **Step 4: Route smoke test**

Run: `docker compose run --rm app php artisan route:list | grep shift`
Expected: all shift routes visible.

- [ ] **Step 5: Commit HTTP**

```bash
git add src/backend/app/Modules/Shift/Infrastructure/Http src/backend/app/Modules/Shift/Routes src/backend/routes/api.php
git commit -m "feat(shift): add HTTP API"
```

---

### Task 9: Permissions and tests

**Files:**
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php`
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/RoleSeeder.php`
- Create: `src/backend/tests/Feature/Modules/Shift/ShiftApiTest.php`

- [ ] **Step 1: Add shift permissions**

Add `shift.template.view`, `shift.template.create`, `shift.template.update`.

- [ ] **Step 2: Grant roles**

Grant all `shift.*` to `SUPER_ADMIN` and `HR_MANAGER`.

- [ ] **Step 3: Write feature tests**

Cover create/list/show template, assign shift, overlap blocked, authz 401/403.

- [ ] **Step 4: Run targeted tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Shift tests/Feature/Modules/Shift --compact`
Expected: PASS.

- [ ] **Step 5: Commit tests/permissions**

```bash
git add src/backend/app/Modules/Identity/Infrastructure/Seeders src/backend/tests/Feature/Modules/Shift
git commit -m "test(shift): add coverage and permissions"
```

---

### Task 10: README and final verification

**Files:**
- Create: `src/backend/app/Modules/Shift/README.md`

- [ ] **Step 1: Write README**

Document aggregates, rules JSON, endpoints, permissions, test commands.

- [ ] **Step 2: Run full backend tests**

Run: `docker compose run --rm app php artisan test --compact`
Expected: PASS.

- [ ] **Step 3: Check git status**

Run: `git status --short`
Expected: clean.

- [ ] **Step 4: Commit README**

```bash
git add src/backend/app/Modules/Shift/README.md
git commit -m "docs(shift): add module README"
```

---

## Execution Notes

- Execute in isolated worktree `.worktrees/shift` on branch `feature/shift`.
- End workflow for this module: push branch + create PR. Do not merge locally into `main`.
- Keep overtime/flexibility rules as JSON VOs in Phase 2; no extra relational tables yet.
- Attendance module will consume Shift APIs later; don't build Attendance logic here.
