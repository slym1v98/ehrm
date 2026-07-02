# Phase 2 Leave Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Phase 2 Leave module with leave types, policies, requests, balances, inline approval, permissions, and tests.

**Architecture:** Strict DDD tactical structure under `src/backend/app/Modules/Leave`: Domain is pure PHP, Application orchestrates commands/queries, Infrastructure owns Eloquent/HTTP/routes. Approval is inline (`pending → approved|rejected|cancelled`), not Workflow-backed.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL 16 JSONB/UUIDs, Sanctum, Eloquent repositories, PHPUnit.

---

## File Map

- `src/backend/app/Modules/Leave/Domain/**`: aggregates, value objects, events, exceptions, repository contracts.
- `src/backend/app/Modules/Leave/Application/**`: commands, handlers, queries, query handlers.
- `src/backend/app/Modules/Leave/Infrastructure/**`: Eloquent models/repositories, HTTP controllers/requests/resources, seeders.
- `src/backend/app/Modules/Leave/Routes/api.php`: module routes under `/api/v1`.
- `src/backend/database/migrations/2026_07_02_06000*_create_leave_*.php`: Leave schema.
- `src/backend/app/Providers/AppServiceProvider.php`: bind Leave repository interfaces.
- `src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php`: add `leave.*` permissions.
- `src/backend/app/Modules/Identity/Infrastructure/Seeders/RoleSeeder.php`: grant `leave.*` to `SUPER_ADMIN` and `HR_MANAGER`.
- `src/backend/routes/api.php`: require Leave route file.
- `src/backend/tests/Unit/Modules/Leave/**`: domain/application tests.
- `src/backend/tests/Feature/Modules/Leave/**`: HTTP + authz tests.
- `src/backend/app/Modules/Leave/README.md`: module behavior, endpoints, permissions, test commands.

---

### Task 0: Worktree setup

**Files:** none

- [ ] **Step 1: Create isolated worktree**

Run from repo root:

```bash
git fetch origin
git worktree add .worktrees/leave -b feature/leave origin/main
cd .worktrees/leave
```

Expected: new worktree on `feature/leave`.

- [ ] **Step 2: Confirm clean tree**

Run:

```bash
git status --short
```

Expected: no output.

---

### Task 1: Database schema

**Files:**
- Create: `src/backend/database/migrations/2026_07_02_060001_create_leave_types_table.php`
- Create: `src/backend/database/migrations/2026_07_02_060002_create_leave_policies_table.php`
- Create: `src/backend/database/migrations/2026_07_02_060003_create_leave_balances_table.php`
- Create: `src/backend/database/migrations/2026_07_02_060004_create_leave_requests_table.php`

- [ ] **Step 1: Create `leave_types` migration**

Columns: UUID `id`, unique `code`, `name`, `is_balance_tracked` default true, `is_active` default true, `sort_order` default 0, timestamps. Add index on `is_active`.

- [ ] **Step 2: Create `leave_policies` migration**

Columns: UUID `id`, FK `leave_type_id`, `valid_from`, nullable `valid_until`, nullable `max_consecutive_days`, `requires_attachment` default false, nullable `carry_over_limit`, nullable `carry_over_expiry_months`, `half_day_allowed` default true, `hourly_allowed` default false, timestamps. Add index `(leave_type_id, valid_from, valid_until)`.

- [ ] **Step 3: Create `leave_balances` migration**

Columns: UUID `id`, UUID `employee_id`, FK `leave_type_id`, `year`, integer columns `opening`, `accrued`, `used`, `carried_over`, `expired` default 0, timestamps. Add unique `(employee_id, leave_type_id, year)`.

- [ ] **Step 4: Create `leave_requests` migration**

Columns: UUID `id`, UUID `employee_id`, FK `leave_type_id`, dates `start_at`, `end_at`, `duration_unit`, `duration_minutes`, nullable `reason`, `status` default `pending`, nullable UUID `approved_by`, nullable timestamp `approved_at`, nullable `rejected_reason`, nullable integer `balance_before`, timestamps. Add indexes `(employee_id,start_at,end_at)`, `(employee_id,status)`, `(leave_type_id,status)`.

- [ ] **Step 5: Run migrations**

Run:

```bash
docker compose run --rm app php artisan migrate:fresh --seed
```

Expected: PASS; all `leave_*` tables exist.

- [ ] **Step 6: Commit schema**

```bash
git add src/backend/database/migrations/2026_07_02_06000*_create_leave_*.php
git commit -m "feat(leave): add schema"
```

---

### Task 2: Eloquent models and seeders

**Files:**
- Create: `src/backend/app/Modules/Leave/Infrastructure/Persistence/Eloquent/LeaveTypeModel.php`
- Create: `src/backend/app/Modules/Leave/Infrastructure/Persistence/Eloquent/LeavePolicyModel.php`
- Create: `src/backend/app/Modules/Leave/Infrastructure/Persistence/Eloquent/LeaveBalanceModel.php`
- Create: `src/backend/app/Modules/Leave/Infrastructure/Persistence/Eloquent/LeaveRequestModel.php`
- Create: `src/backend/app/Modules/Leave/Infrastructure/Seeders/LeaveTypeSeeder.php`

- [ ] **Step 1: Create Eloquent models**

Match existing Shift/Attendance model style: `$table`, `$keyType = 'string'`, `$incrementing = false`, guarded/fillable style consistent with repo, casts for bool/date/datetime/int.

- [ ] **Step 2: Create `LeaveTypeSeeder`**

Seed codes: `annual`, `sick`, `unpaid`, `maternity`. Use upsert/idempotent pattern from existing module seeders.

- [ ] **Step 3: Run migration+seed**

```bash
docker compose run --rm app php artisan migrate:fresh --seed
```

Expected: PASS.

- [ ] **Step 4: Commit models/seeders**

```bash
git add src/backend/app/Modules/Leave/Infrastructure/Persistence/Eloquent src/backend/app/Modules/Leave/Infrastructure/Seeders
git commit -m "feat(leave): add eloquent models"
```

---

### Task 3: Domain value objects, events, exceptions

**Files:**
- Create: `src/backend/app/Modules/Leave/Domain/ValueObjects/DurationUnit.php`
- Create: `src/backend/app/Modules/Leave/Domain/ValueObjects/LeaveStatus.php`
- Create: `src/backend/app/Modules/Leave/Domain/ValueObjects/LeavePeriod.php`
- Create: `src/backend/app/Modules/Leave/Domain/Events/*.php`
- Create: `src/backend/app/Modules/Leave/Domain/Exceptions/*.php`
- Test: `src/backend/tests/Unit/Modules/Leave/Domain/LeaveValueObjectTest.php`

- [ ] **Step 1: Write value-object tests**

Cover: `DurationUnit::from('day')`, invalid unit throws; `LeaveStatus` valid transitions; `LeavePeriod` rejects end before start and computes duration minutes for day/half/hour.

- [ ] **Step 2: Implement value objects**

Use PHP enums where existing modules use enums; otherwise immutable value-object classes. Keep mapping: day=480, half_day=240, hour=60.

- [ ] **Step 3: Add events/exceptions**

Events: `LeaveRequestSubmitted`, `LeaveRequestApproved`, `LeaveRequestRejected`, `LeaveRequestCancelled`, `LeaveBalanceAdjusted`. Exceptions map to spec names.

- [ ] **Step 4: Run unit tests**

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Leave/Domain/LeaveValueObjectTest.php --compact
```

Expected: PASS.

- [ ] **Step 5: Commit domain primitives**

```bash
git add src/backend/app/Modules/Leave/Domain src/backend/tests/Unit/Modules/Leave/Domain/LeaveValueObjectTest.php
git commit -m "feat(leave): add domain primitives"
```

---

### Task 4: Domain aggregates

**Files:**
- Create: `src/backend/app/Modules/Leave/Domain/Aggregates/LeaveType/*`
- Create: `src/backend/app/Modules/Leave/Domain/Aggregates/LeavePolicy/*`
- Create: `src/backend/app/Modules/Leave/Domain/Aggregates/LeaveRequest/*`
- Create: `src/backend/app/Modules/Leave/Domain/Aggregates/LeaveBalance/*`
- Test: `src/backend/tests/Unit/Modules/Leave/Domain/LeaveRequestTest.php`
- Test: `src/backend/tests/Unit/Modules/Leave/Domain/LeaveBalanceTest.php`

- [ ] **Step 1: Write aggregate tests**

Cover: request submit starts pending; approve sets approved fields; reject requires pending; cancel pending no balance impact; approved cancel allowed by method call; illegal transitions throw. Balance deduct reduces remaining; insufficient balance throws; restore reduces used.

- [ ] **Step 2: Implement aggregate IDs and aggregates**

Use existing module ID style. Keep aggregates framework-free. No Eloquent imports.

- [ ] **Step 3: Run domain tests**

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Leave/Domain --compact
```

Expected: PASS.

- [ ] **Step 4: Commit aggregates**

```bash
git add src/backend/app/Modules/Leave/Domain/Aggregates src/backend/tests/Unit/Modules/Leave/Domain
git commit -m "feat(leave): add aggregates"
```

---

### Task 5: Repositories and DI bindings

**Files:**
- Create: `src/backend/app/Modules/Leave/Domain/Repositories/*RepositoryInterface.php`
- Create: `src/backend/app/Modules/Leave/Infrastructure/Persistence/Repositories/Eloquent*Repository.php`
- Modify: `src/backend/app/Providers/AppServiceProvider.php`
- Test: `src/backend/tests/Unit/Modules/Leave/Infrastructure/LeaveRepositoryTest.php`

- [ ] **Step 1: Add repository interfaces**

Methods from spec: type `findById`, `findByCode`, `all`, `save`; policy `findByType`; request `findById`, `findOverlapping`, `findByEmployee`, `save`; balance `findByEmployeeTypeYear`, `save`.

- [ ] **Step 2: Add Eloquent repositories**

Map Eloquent models ↔ domain aggregates. `findOverlapping`: employee match, status in `pending|approved`, dates overlap (`start_at <= end && end_at >= start`).

- [ ] **Step 3: Bind interfaces in `AppServiceProvider`**

Follow existing Attendance/Shift binding style.

- [ ] **Step 4: Run repository tests**

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Leave/Infrastructure/LeaveRepositoryTest.php --compact
```

Expected: PASS.

- [ ] **Step 5: Commit repositories**

```bash
git add src/backend/app/Modules/Leave/Domain/Repositories src/backend/app/Modules/Leave/Infrastructure/Persistence/Repositories src/backend/app/Providers/AppServiceProvider.php src/backend/tests/Unit/Modules/Leave/Infrastructure/LeaveRepositoryTest.php
git commit -m "feat(leave): add repositories"
```

---

### Task 6: Application commands and handlers

**Files:**
- Create: `src/backend/app/Modules/Leave/Application/Commands/LeaveRequest/*Command.php`
- Create: `src/backend/app/Modules/Leave/Application/CommandHandlers/LeaveRequest/*Handler.php`
- Create: `src/backend/app/Modules/Leave/Application/Queries/LeaveRequest/*Query.php`
- Create: `src/backend/app/Modules/Leave/Application/QueryHandlers/*Handler.php`
- Test: `src/backend/tests/Unit/Modules/Leave/Application/LeaveRequestHandlerTest.php`

- [ ] **Step 1: Write handler tests**

Cover submit success, overlap 409 exception, insufficient balance exception, approve deducts balance, reject changes status, cancel approved restores balance.

- [ ] **Step 2: Implement commands/queries as readonly DTOs**

Use scalar IDs/strings/dates at app boundary; convert to domain IDs/VOs in handlers.

- [ ] **Step 3: Implement handlers**

Use repository interfaces only. Wrap approval/cancel balance changes in DB transaction if existing app pattern supports it; otherwise repository save order: balance then request.

- [ ] **Step 4: Run application tests**

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Leave/Application/LeaveRequestHandlerTest.php --compact
```

Expected: PASS.

- [ ] **Step 5: Commit application layer**

```bash
git add src/backend/app/Modules/Leave/Application src/backend/tests/Unit/Modules/Leave/Application
git commit -m "feat(leave): add application layer"
```

---

### Task 7: HTTP API and routes

**Files:**
- Create: `src/backend/app/Modules/Leave/Infrastructure/Http/Controllers/*Controller.php`
- Create: `src/backend/app/Modules/Leave/Infrastructure/Http/Requests/*Request.php`
- Create: `src/backend/app/Modules/Leave/Infrastructure/Http/Resources/*Resource.php`
- Create: `src/backend/app/Modules/Leave/Routes/api.php`
- Modify: `src/backend/routes/api.php`

- [ ] **Step 1: Add FormRequests**

Validate submit/approve/reject/cancel exactly as spec. Keep business validation in handlers.

- [ ] **Step 2: Add Resources**

Resources: `LeaveRequestResource`, `LeaveTypeResource`, `LeavePolicyResource`, `LeaveBalanceResource`.

- [ ] **Step 3: Add Controllers**

Thin controllers only. No domain logic. Delegate to handlers/query handlers.

- [ ] **Step 4: Add routes**

Register routes under `/api/v1`, `auth:sanctum`, permission middleware: leave types, policies, requests, balances.

- [ ] **Step 5: Register route file**

Add `require __DIR__ . '/../app/Modules/Leave/Routes/api.php';` to `src/backend/routes/api.php`.

- [ ] **Step 6: Smoke route list**

```bash
docker compose run --rm app php artisan route:list | grep leave
```

Expected: Leave routes visible.

- [ ] **Step 7: Commit HTTP**

```bash
git add src/backend/app/Modules/Leave/Infrastructure/Http src/backend/app/Modules/Leave/Routes src/backend/routes/api.php
git commit -m "feat(leave): add HTTP API"
```

---

### Task 8: Permissions and feature tests

**Files:**
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php`
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/RoleSeeder.php`
- Test: `src/backend/tests/Feature/Modules/Leave/LeaveApiTest.php`

- [ ] **Step 1: Add leave permissions**

Add: `leave.type.view`, `leave.policy.view`, `leave.request.create`, `leave.request.view`, `leave.request.approve`, `leave.request.reject`, `leave.request.cancel`, `leave.balance.view`.

- [ ] **Step 2: Grant HR roles**

Grant all `leave.*` to `HR_MANAGER`; `SUPER_ADMIN` gets all permissions through existing all-permission rule if present.

- [ ] **Step 3: Write feature tests**

Cover unauthenticated 401, missing permission 403, create leave 201, duplicate/overlap 409, approve deducts balance, reject requires reason, cancel approved restores balance, list balances returns computed remaining.

- [ ] **Step 4: Run feature tests**

```bash
docker compose run --rm app php artisan test tests/Feature/Modules/Leave/LeaveApiTest.php --compact
```

Expected: PASS.

- [ ] **Step 5: Commit permissions/tests**

```bash
git add src/backend/app/Modules/Identity/Infrastructure/Seeders src/backend/tests/Feature/Modules/Leave/LeaveApiTest.php
git commit -m "test(leave): add API coverage and permissions"
```

---

### Task 9: Attendance leave-window integration

**Files:**
- Create: `src/backend/app/Modules/Leave/Domain/Services/LeaveWindowInterface.php`
- Create: `src/backend/app/Modules/Leave/Infrastructure/Persistence/Repositories/EloquentLeaveWindowRepository.php`
- Modify: `src/backend/app/Providers/AppServiceProvider.php`
- Test: `src/backend/tests/Unit/Modules/Leave/Infrastructure/LeaveWindowRepositoryTest.php`

- [ ] **Step 1: Add interface**

`getLeaveWindows(string $employeeId, CarbonImmutable $start, CarbonImmutable $end): array` returns approved leave windows only.

- [ ] **Step 2: Add Eloquent implementation**

Query `leave_requests` by employee, status approved, overlapping range.

- [ ] **Step 3: Bind implementation**

Bind interface in `AppServiceProvider`.

- [ ] **Step 4: Run integration test**

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Leave/Infrastructure/LeaveWindowRepositoryTest.php --compact
```

Expected: PASS.

- [ ] **Step 5: Commit leave-window service**

```bash
git add src/backend/app/Modules/Leave/Domain/Services src/backend/app/Modules/Leave/Infrastructure/Persistence/Repositories/EloquentLeaveWindowRepository.php src/backend/app/Providers/AppServiceProvider.php src/backend/tests/Unit/Modules/Leave/Infrastructure/LeaveWindowRepositoryTest.php
git commit -m "feat(leave): expose approved leave windows"
```

---

### Task 10: README and final verification

**Files:**
- Create: `src/backend/app/Modules/Leave/README.md`

- [ ] **Step 1: Write README**

Document aggregates, status machine, balance rules, endpoints, permissions, YAGNI skips, test commands.

- [ ] **Step 2: Run targeted tests**

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Leave tests/Feature/Modules/Leave --compact
```

Expected: PASS.

- [ ] **Step 3: Run full backend tests**

```bash
docker compose run --rm app php artisan test --compact
```

Expected: PASS; report count.

- [ ] **Step 4: Check spec coverage**

Review `docs/superpowers/specs/2026-07-02-phase2-leave-design.md` sections 1–14 against implementation. Report every AC 1–12 as covered/gap.

- [ ] **Step 5: Commit docs**

```bash
git add src/backend/app/Modules/Leave/README.md
git commit -m "docs(leave): add module README"
```

- [ ] **Step 6: Push branch**

```bash
git push -u origin feature/leave
```

---

## Execution Notes

- Execute in isolated worktree `.worktrees/leave` on branch `feature/leave`.
- Keep commits small in the task order above.
- Do not add Workflow BC integration; inline approval is intentional.
- Do not add accrual scheduler; seed/manual balance adjustment only.
- Do not add notifications; events are enough for later fan-out.
- Do not mutate Attendance tables; Leave exposes approved windows as read side.
- End workflow: push branch and create PR; do not merge locally into `main`.

## Self-Review

- Spec coverage: schema, aggregates, status machine, balance rules, endpoints, permissions, tests, README covered.
- Placeholder scan: no TBD/TODO/implement-later placeholders.
- Type consistency: names match spec paths and API routes; repository/interface names match handler references.
