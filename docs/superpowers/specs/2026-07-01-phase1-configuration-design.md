# Phase 1 Sub-2 Design — Configuration

Version: 0.1
Date: 2026-07-01
Status: Design approved (brainstorming)

## 1. Scope

Build Configuration module (`app/Modules/Configuration/`) as the second sub-project of iHRM Phase 1 Core Platform. Covers lookup groups/values, code generation rules, system settings, holiday calendars, and notification thresholds.

## 2. Architecture

Strict DDD layered pattern identical to Identity module. Module directory:

```
app/Modules/Configuration/Domain/       — aggregates, VOs, events, repo interfaces
app/Modules/Configuration/Application/  — commands/queries + handlers
app/Modules/Configuration/Infrastructure — Eloquent, controllers, routes, seeders
```

Configuration is a Shared Kernel bounded context — all modules read from it. API routes protected by `permission:configuration.*` middleware.

## 3. Data model

### lookup_groups
- id uuid pk
- code varchar(100) unique (lowercase snake_case)
- name varchar(255)
- description text nullable
- active bool default true
- timestamps

### lookup_values
- id uuid pk
- group_id FK lookup_groups cascade
- code varchar(100)
- name varchar(255)
- description text nullable
- sort_order int default 0
- active bool default true
- metadata json nullable
- timestamps
- unique(group_id, code)

### code_generation_rules
- id uuid pk
- entity_type varchar(100) unique (employee, contract)
- prefix varchar(50)
- pattern varchar(100) ({prefix}-{yyyy}-{seq})
- sequence_padding int default 5
- next_number bigint default 1
- active bool default true
- timestamps

### system_settings
- id uuid pk
- key varchar(150) unique
- value text nullable
- value_type varchar(30) default string (string|int|bool|json)
- group varchar(100) nullable
- description text nullable
- editable bool default true
- timestamps

### holiday_calendars
- id uuid pk
- code varchar(100) unique
- name varchar(255)
- year int
- active bool default true
- timestamps

### holidays
- id uuid pk
- calendar_id FK holiday_calendars cascade
- date date
- name varchar(255)
- type varchar(50) default public (public|company)
- paid bool default true
- metadata json nullable
- timestamps
- unique(calendar_id, date)

### notification_thresholds
- id uuid pk
- code varchar(100) unique
- target_type varchar(100) (contract|document)
- days_before int (>=0)
- channel varchar(50) default in_app (in_app|email)
- active bool default true
- metadata json nullable
- timestamps

## 4. API endpoints

All under `/api/v1/config/`. Protected by `auth:sanctum` + `permission:configuration.*`.

| Method | Path | Permission |
|--------|------|-----------|
| GET | /config/lookup-groups | configuration.lookup.list |
| POST | /config/lookup-groups | configuration.lookup.manage |
| GET | /config/lookup-groups/{id} | configuration.lookup.list |
| PATCH | /config/lookup-groups/{id} | configuration.lookup.manage |
| POST | /config/lookup-groups/{id}/deactivate | configuration.lookup.manage |
| POST | /config/lookup-groups/{id}/values | configuration.lookup.manage |
| PATCH | /config/lookup-values/{id} | configuration.lookup.manage |
| POST | /config/lookup-values/{id}/deactivate | configuration.lookup.manage |
| GET | /config/code-generation-rules | configuration.code_generation.list |
| POST | /config/code-generation-rules | configuration.code_generation.manage |
| PATCH | /config/code-generation-rules/{id} | configuration.code_generation.manage |
| POST | /config/code-generation-rules/{id}/generate-preview | configuration.code_generation.manage |
| POST | /config/code-generation-rules/{id}/generate-next | configuration.code_generation.manage |
| GET | /config/system-settings | configuration.setting.list |
| PATCH | /config/system-settings/{key} | configuration.setting.manage |
| GET | /config/holiday-calendars | configuration.holiday.list |
| POST | /config/holiday-calendars | configuration.holiday.manage |
| GET | /config/holiday-calendars/{id} | configuration.holiday.list |
| PATCH | /config/holiday-calendars/{id} | configuration.holiday.manage |
| POST | /config/holiday-calendars/{id}/holidays | configuration.holiday.manage |
| PATCH | /config/holidays/{id} | configuration.holiday.manage |
| DELETE | /config/holidays/{id} | configuration.holiday.manage |
| GET | /config/notification-thresholds | configuration.notification_threshold.list |
| POST | /config/notification-thresholds | configuration.notification_threshold.manage |
| PATCH | /config/notification-thresholds/{id} | configuration.notification_threshold.manage |
| POST | /config/notification-thresholds/{id}/deactivate | configuration.notification_threshold.manage |

## 5. Code generation behavior

- `generate-preview` reads rule without incrementing `next_number`. Returns candidate code.
- `generate-next` locks row `FOR UPDATE`, reads `next_number`, generates code, increments, returns generated code.
- Pattern tokens: `{prefix}`, `{yyyy}`, `{yy}`, `{mm}`, `{dd}`, `{seq}`.
- Consumer modules call `generate-next` inside their own transaction.

## 6. Permissions

Seeded:
```
configuration.lookup.list, .manage
configuration.code_generation.list, .manage
configuration.setting.list, .manage
configuration.holiday.list, .manage
configuration.notification_threshold.list, .manage
```

## 7. Seed data

### Lookup groups
- `gender`: male, female, other
- `employment_type`: full_time, part_time, contract, intern, probation
- `contract_type`: indefinite, fixed_term, seasonal, apprenticeship
- `marital_status`: single, married, divorced, widowed
- `education_level`: high_school, bachelor, master, doctorate, other
- `employee_status`: draft, onboarding, probation, active, resigned, archived

### System settings
- `company.name` → "iHRM Enterprise" (editable)
- `company.tax_id` → null (editable)
- `locale.default` → "en" (editable)
- `locale.timezone` → "UTC" (editable)
- `employee.code_generation_rule` → "employee" (entity type reference, not editable via settings)

### Code generation rules
| entity_type | prefix | pattern | padding |
|-------------|--------|---------|---------|
| employee | EMP | {prefix}-{seq} | 5 |
| contract | CTR | {prefix}-{seq} | 5 |

## 8. Acceptance criteria

- All 26+ API endpoints functional and documented
- CRUD for lookup groups/values with unique-in-group constraint
- Code generation preview does not increment; generate-next increments atomically
- System settings respect `editable=false` guard
- Holiday calendars enforce unique date per calendar
- Holiday calendar year validation
- Notification threshold days_before >= 0
- All endpoints protected by `permission:configuration.*` middleware
- All tests pass (unit + feature)
- Error responses follow ErrorResource format
- Paginated responses follow PaginatedCollection format
- Seeder creates default lookup groups, settings, and code generation rules

## 9. Risks

- Code generation atomicity depends on DB transaction — ensure consumer Employee/Contract modules call within transaction with `FOR UPDATE`.
- `metadata` JSON columns used sparingly; no deep query on JSON in Phase 1.
- Holiday calendar validation (year range) is app-level; no DB enforcement.
- Data scope not applied to Configuration — these are admin-only settings, not employee-scoped data.
