# Phase 1 Configuration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Phase 1 Configuration module: lookups, code generation rules, system settings, holidays, and notification thresholds.

**Architecture:** Strict DDD-style module under `src/backend/app/Modules/Configuration`, consistent with Identity. Use UUID tables, Eloquent repositories, HTTP controllers/resources/requests, seed defaults, and `permission:configuration.*` middleware.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL, Sanctum, PHPUnit, Eloquent, Docker Compose.

---

## File Map

- `src/backend/app/Modules/Configuration/Domain/**`: aggregates, value objects, events, exceptions, repository interfaces.
- `src/backend/app/Modules/Configuration/Application/**`: code-generation service and handlers.
- `src/backend/app/Modules/Configuration/Infrastructure/Persistence/**`: Eloquent models/repositories.
- `src/backend/app/Modules/Configuration/Infrastructure/Http/**`: controllers, requests, resources.
- `src/backend/app/Modules/Configuration/Infrastructure/Seeders/**`: defaults.
- `src/backend/app/Modules/Configuration/Routes/api.php`: module routes.
- `src/backend/database/migrations/*configuration*.php`: tables.
- `src/backend/database/seeders/DatabaseSeeder.php`: call config seeders.
- `src/backend/routes/api.php`: require module routes.
- `src/backend/tests/{Unit,Feature}/Modules/Configuration/**`: tests.

---

### Task 1: Database schema

**Files:**
- Create: `src/backend/database/migrations/2026_07_01_100001_create_lookup_groups_table.php`
- Create: `src/backend/database/migrations/2026_07_01_100002_create_lookup_values_table.php`
- Create: `src/backend/database/migrations/2026_07_01_100003_create_code_generation_rules_table.php`
- Create: `src/backend/database/migrations/2026_07_01_100004_create_system_settings_table.php`
- Create: `src/backend/database/migrations/2026_07_01_100005_create_holiday_calendars_table.php`
- Create: `src/backend/database/migrations/2026_07_01_100006_create_holidays_table.php`
- Create: `src/backend/database/migrations/2026_07_01_100007_create_notification_thresholds_table.php`

- [ ] **Step 1: Write migrations**

Create exact schemas from `docs/superpowers/specs/2026-07-01-phase1-configuration-design.md` Section 3. Use `$table->uuid('id')->primary()`, `HasUuids` compatible IDs, unique indexes, JSON columns nullable, and FK cascade from child tables to parent tables.

- [ ] **Step 2: Run migration verification**

```bash
docker compose run --rm app php artisan migrate:fresh
```

Expected: all existing + new Configuration migrations run successfully.

- [ ] **Step 3: Commit**

```bash
git add src/backend/database/migrations
git commit -m "feat(configuration): add configuration database schema"
```

---

### Task 2: Eloquent models and repositories

**Files:**
- Create: `src/backend/app/Modules/Configuration/Infrastructure/Persistence/Eloquent/{LookupGroupModel,LookupValueModel,CodeGenerationRuleModel,SystemSettingModel,HolidayCalendarModel,HolidayModel,NotificationThresholdModel}.php`
- Create: `src/backend/app/Modules/Configuration/Domain/Repositories/{LookupRepositoryInterface,CodeGenerationRuleRepositoryInterface,SystemSettingRepositoryInterface,HolidayCalendarRepositoryInterface,NotificationThresholdRepositoryInterface}.php`
- Create: `src/backend/app/Modules/Configuration/Infrastructure/Persistence/Repositories/{EloquentLookupRepository,EloquentCodeGenerationRuleRepository,EloquentSystemSettingRepository,EloquentHolidayCalendarRepository,EloquentNotificationThresholdRepository}.php`
- Modify: `src/backend/app/Providers/AppServiceProvider.php`
- Test: `src/backend/tests/Unit/Modules/Configuration/ConfigurationRepositoryTest.php`

- [ ] **Step 1: Write repository tests**

Tests must verify creating/finding:
- Lookup group with values and unique codes
- CodeGenerationRule by entity_type
- SystemSetting by key
- HolidayCalendar with holidays
- NotificationThreshold by code

- [ ] **Step 2: Implement Eloquent models**

Each model uses `HasUuids`, `$incrementing=false`, `$keyType='string'`, `$guarded=[]`. Relationships:
- `LookupGroupModel::values()` hasMany
- `LookupValueModel::group()` belongsTo
- `HolidayCalendarModel::holidays()` hasMany
- `HolidayModel::calendar()` belongsTo

- [ ] **Step 3: Implement repositories and bindings**

Repositories expose CRUD-ish methods: `findById`, `findByCode`/`findByKey`, `save`, `listPaginated`. Bind interfaces in `AppServiceProvider`.

- [ ] **Step 4: Run tests**

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Configuration/ConfigurationRepositoryTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/backend/app/Modules/Configuration src/backend/app/Providers/AppServiceProvider.php src/backend/tests/Unit/Modules/Configuration
git commit -m "feat(configuration): add eloquent models and repositories"
```

---

### Task 3: Domain aggregates and code generation service

**Files:**
- Create: `src/backend/app/Modules/Configuration/Domain/Aggregates/**`
- Create: `src/backend/app/Modules/Configuration/Domain/Events/**`
- Create: `src/backend/app/Modules/Configuration/Domain/Exceptions/**`
- Create: `src/backend/app/Modules/Configuration/Application/Services/CodeGenerator.php`
- Test: `src/backend/tests/Unit/Modules/Configuration/Domain/CodeGeneratorTest.php`

- [ ] **Step 1: Write code generator tests**

Tests:
- `{prefix}-{yyyy}-{seq}` with padding returns expected code
- preview does not increment next_number
- generate-next increments next_number
- unsupported token throws validation exception

- [ ] **Step 2: Implement minimal domain classes**

Keep aggregates thin. Required invariants:
- Lookup group code unique handled by DB/repo
- Lookup value code unique within group handled by DB/repo
- SystemSetting editable guard
- Holiday year range app-level validation
- NotificationThreshold `days_before >= 0`

- [ ] **Step 3: Implement CodeGenerator service**

Methods:
- `preview(CodeGenerationRuleModel $rule, ?DateTimeInterface $date = null): string`
- `generateNext(string $entityType): string` with DB transaction + `lockForUpdate()`

Supported tokens: `{prefix}`, `{yyyy}`, `{yy}`, `{mm}`, `{dd}`, `{seq}`.

- [ ] **Step 4: Run tests**

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Configuration/Domain/CodeGeneratorTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/backend/app/Modules/Configuration/Domain src/backend/app/Modules/Configuration/Application src/backend/tests/Unit/Modules/Configuration/Domain
git commit -m "feat(configuration): add domain model and code generator"
```

---

### Task 4: Seed defaults and permissions

**Files:**
- Create: `src/backend/app/Modules/Configuration/Infrastructure/Seeders/ConfigurationSeeder.php`
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php`
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/RoleSeeder.php`
- Modify: `src/backend/database/seeders/DatabaseSeeder.php`
- Test: `src/backend/tests/Feature/Modules/Configuration/ConfigurationSeederTest.php`

- [ ] **Step 1: Add Configuration permissions**

Add permission codes:
```text
configuration.lookup.list
configuration.lookup.manage
configuration.code_generation.list
configuration.code_generation.manage
configuration.setting.list
configuration.setting.manage
configuration.holiday.list
configuration.holiday.manage
configuration.notification_threshold.list
configuration.notification_threshold.manage
```

Grant all to `SUPER_ADMIN` through existing RoleSeeder behavior.

- [ ] **Step 2: Implement ConfigurationSeeder**

Seed lookup groups/values, system settings, code generation rules, default current-year Vietnam holiday calendar with at least New Year, and notification thresholds for contract/document expiry.

- [ ] **Step 3: Register seeder**

In `DatabaseSeeder`, call Identity seeders first, then `ConfigurationSeeder`.

- [ ] **Step 4: Run tests**

```bash
docker compose run --rm app php artisan test tests/Feature/Modules/Configuration/ConfigurationSeederTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/backend/app/Modules/Configuration/Infrastructure/Seeders src/backend/app/Modules/Identity/Infrastructure/Seeders src/backend/database/seeders/DatabaseSeeder.php src/backend/tests/Feature/Modules/Configuration/ConfigurationSeederTest.php
git commit -m "feat(configuration): seed defaults and permissions"
```

---

### Task 5: HTTP requests, resources, controllers, routes

**Files:**
- Create: `src/backend/app/Modules/Configuration/Infrastructure/Http/Requests/*.php`
- Create: `src/backend/app/Modules/Configuration/Infrastructure/Http/Resources/*.php`
- Create: `src/backend/app/Modules/Configuration/Infrastructure/Http/Controllers/{LookupController,CodeGenerationRuleController,SystemSettingController,HolidayCalendarController,NotificationThresholdController}.php`
- Create: `src/backend/app/Modules/Configuration/Routes/api.php`
- Modify: `src/backend/routes/api.php`
- Test: `src/backend/tests/Feature/Modules/Configuration/ConfigurationHttpTest.php`

- [ ] **Step 1: Write feature tests**

Tests must cover:
- Admin can list/create lookup group and add lookup value
- User without config permission gets 403
- Admin can preview and generate next code
- Admin cannot update non-editable setting
- Admin can create calendar and holiday
- Admin can create notification threshold

- [ ] **Step 2: Implement requests/resources/controllers**

Use `PaginatedCollection` for list endpoints and existing `ErrorResource` behavior for validation/errors. Use FormRequest validation for all invariants possible.

- [ ] **Step 3: Implement routes**

Require module routes in `src/backend/routes/api.php`. All routes under `/api/v1/config`, protected by `auth:sanctum` and `permission:configuration.*`.

- [ ] **Step 4: Run feature tests**

```bash
docker compose run --rm app php artisan test tests/Feature/Modules/Configuration/ConfigurationHttpTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/backend/app/Modules/Configuration/Infrastructure/Http src/backend/app/Modules/Configuration/Routes src/backend/routes/api.php src/backend/tests/Feature/Modules/Configuration/ConfigurationHttpTest.php
git commit -m "feat(configuration): expose configuration HTTP API"
```

---

### Task 6: Documentation and final verification

**Files:**
- Create: `src/backend/app/Modules/Configuration/README.md`
- Modify: `docs/api/openapi/01-core-platform.openapi.yaml` if endpoint contract drift exists

- [ ] **Step 1: Write module README**

Document scope, routes, permissions, seed data, code generation behavior, and test commands.

- [ ] **Step 2: Run full backend tests**

```bash
docker compose run --rm app php artisan test
```

Expected: PASS.

- [ ] **Step 3: Run smoke test**

```bash
docker compose run --rm app php artisan migrate:fresh --seed
curl -s -X POST http://api.ihrm.test/api/v1/auth/login -H 'Content-Type: application/json' -d '{"email":"admin@ihrm.local","password":"password"}'
```

Expected: access token returned.

- [ ] **Step 4: Commit docs**

```bash
git add src/backend/app/Modules/Configuration/README.md docs/api/openapi/01-core-platform.openapi.yaml
git commit -m "docs(configuration): document configuration module"
```

---

## Self-Review

- Spec coverage: all data models, permissions, seed data, code generation behavior, endpoints, tests covered.
- Placeholder scan: no TBD/TODO placeholders.
- Scope check: one bounded context, single implementation plan is appropriate.
