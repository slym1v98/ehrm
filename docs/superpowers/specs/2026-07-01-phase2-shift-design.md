# Phase 2 Shift BC Design

Version: 0.1
Date: 2026-07-01
Status: Design approved (brainstorming)

## 1. Scope

Build Shift module (`app/Modules/Shift/`) as the first sub-project of iHRM Phase 2 Workforce Operations. Covers shift template definitions with overtime/flexibility rules, and shift-to-employee/department assignments with recurrence support.

**In scope:** ShiftTemplate CRUD + activate/deactivate (including overtime_rules/flexibility_rules as JSON VOs), ShiftAssignment assign/end/change with overlap guard, RecurrenceRule VO for repeating patterns, permission code integration with Identity module, full test suite (domain unit + application + feature).

**Out of scope:** Attendance (consumes Shift reads), leave overlap with shift, payroll integration, shift swap/trade workflows, real-time clock integration/geofencing.

## 2. Architecture

**Pattern:** Strict DDD tactical with 3 layers (mirror Employee/Organization modules).

```
Module/Shift/
  Domain/         — Pure PHP, no Laravel deps
  Application/    — Commands/Handlers + Queries
  Infrastructure/ — Eloquent, HTTP controllers, seeders, routes
```

**Dependency:** Domain ← Application ← Infrastructure.

## 3. Module Layout

```
app/Modules/Shift/
  Domain/
    Aggregates/ShiftTemplate/
      ShiftTemplate.php, ShiftTemplateId.php, ShiftWindow.php,
      OvertimeRules.php, FlexibilityRules.php
    Aggregates/ShiftAssignment/
      ShiftAssignment.php, ShiftAssignmentId.php, RecurrenceRule.php
    Events/
      ShiftTemplateCreated.php, ShiftTemplateUpdated.php,
      ShiftTemplateActivated.php, ShiftTemplateDeactivated.php
      ShiftAssigned.php, ShiftAssignmentEnded.php, ShiftAssignmentChanged.php
    Repositories/
      ShiftTemplateRepositoryInterface.php
      ShiftAssignmentRepositoryInterface.php
    Exceptions/
      ShiftTemplateNotFoundException.php, DuplicateShiftTemplateCodeException.php
      ShiftAssignmentNotFoundException.php, OverlappingShiftAssignmentException.php
  Application/
    Commands/ShiftTemplate/
      CreateShiftTemplateCommand.php, UpdateShiftTemplateCommand.php,
      ActivateShiftTemplateCommand.php, DeactivateShiftTemplateCommand.php
    CommandHandlers/ShiftTemplate/...
    Commands/ShiftAssignment/
      AssignShiftCommand.php, EndShiftAssignmentCommand.php, ChangeShiftAssignmentCommand.php
    CommandHandlers/ShiftAssignment/...
    Queries/
      ListShiftTemplatesQuery.php, GetShiftTemplateQuery.php
      GetEmployeeShiftsQuery.php, GetDepartmentShiftsQuery.php
    QueryHandlers/...
  Infrastructure/
    Persistence/
      Eloquent/ShiftTemplateModel.php, ShiftAssignmentModel.php
      Repositories/EloquentShiftTemplateRepository.php, EloquentShiftAssignmentRepository.php
    Http/
      Controllers/ShiftTemplateController.php, ShiftAssignmentController.php
      Requests/CreateShiftTemplateRequest.php, ...
      Resources/ShiftTemplateResource.php, ShiftAssignmentResource.php
    Seeders/ShiftPermissionSeeder.php
  Routes/api.php
```

## 4. Domain Model

### 4.1 ShiftTemplate Aggregate

```
ShiftTemplate {
  id: ShiftTemplateId (UUID VO)
  code: string (unique)
  name: string
  shiftWindow: ShiftWindow (VO)
  breakMinutes: int
  lateToleranceMinutes: int
  overtimeRules: OvertimeRules (VO)
  flexibilityRules: FlexibilityRules (VO)
  payrollAttributionRule: ?string (required when isOvernight)
  active: bool

  static create(): self
  updateDetails(name, window, break, tolerance, overtime, flexibility, attribution): void
  activate(): void
  deactivate(): void

  Invariants:
  - Overnight shift requires payrollAttributionRule.
  - breakMinutes must be < total shift duration.
  - Code is unique.
  - Deactivated template cannot get new assignments.
}
```

**Value objects:**

`ShiftWindow` — { start: string(H:i), end: string(H:i), isOvernight: bool, duration(): int in minutes }
`OvertimeRules` — { minOvertimeThreshold: int, roundingInterval: int, graceMinutes: int, beforeShiftAllowance: int, afterShiftAllowance: int }
`FlexibilityRules` — { maxEarlyArrival: int, maxLateDeparture: int, coreStart: ?string(H:i), coreEnd: ?string(H:i) }

### 4.2 ShiftAssignment Aggregate

```
ShiftAssignment {
  id: ShiftAssignmentId (UUID VO)
  shiftTemplateId: ShiftTemplateId
  assignableType: string ("employee"|"department")
  assignableId: string (UUID ref)
  effectiveFrom: Carbon (date)
  effectiveTo: ?Carbon (date, nullable = ongoing)
  recurrenceRule: ?RecurrenceRule (VO)
  active: bool

  static assign(): self
  endAssignment(endDate): void
  changeTemplate(newTemplateId, effectiveFrom): void

  Invariants:
  - Shift template must be active.
  - effectiveTo >= effectiveFrom.
  - Overlapping active assignments for same entity blocked (dept-level vs employee-level resolved: employee assignment overrides dept).
}
```

**Value object:**

`RecurrenceRule` — { frequency: string(weekly|biweekly|monthly), interval: int, daysOfWeek: int[] (ISO 8601 1-7), rotationGroup: ?string }

## 5. Domain Events

- `ShiftTemplateCreated` — { shiftTemplateId, code, name }
- `ShiftTemplateUpdated` — { shiftTemplateId }
- `ShiftTemplateActivated` — { shiftTemplateId }
- `ShiftTemplateDeactivated` — { shiftTemplateId }
- `ShiftAssigned` — { assignmentId, shiftTemplateId, assignableType, assignableId, effectiveFrom }
- `ShiftAssignmentEnded` — { assignmentId, effectiveTo }
- `ShiftAssignmentChanged` — { assignmentId, oldTemplateId, newTemplateId }

## 6. API Design

Route prefix: `/api/v1`. Protected by Sanctum auth + `permission` middleware.

### ShiftTemplate Endpoints

| Method | Path | Permission | Notes |
|--------|------|------------|-------|
| GET | /shift-templates | shift.template.view | List paginated |
| POST | /shift-templates | shift.template.create | Create |
| GET | /shift-templates/{id} | shift.template.view | Show |
| PATCH | /shift-templates/{id} | shift.template.update | Update |
| POST | /shift-templates/{id}/activate | shift.template.update | Activate |
| POST | /shift-templates/{id}/deactivate | shift.template.update | Deactivate |

### ShiftAssignment Endpoints

| Method | Path | Permission | Notes |
|--------|------|------------|-------|
| POST | /shift-assignments | shift.template.update | Assign employee/dept |
| PATCH | /shift-assignments/{id} | shift.template.update | Change template |
| POST | /shift-assignments/{id}/end | shift.template.update | End assignment |

### Query Endpoints

| Method | Path | Permission | Notes |
|--------|------|------------|-------|
| GET | /employees/{id}/shifts | shift.template.view | Employee schedule |
| GET | /departments/{id}/shifts | shift.template.view | Department schedule |

## 7. Permissions

```php
['shift.template.view',   'template', 'view'],
['shift.template.create', 'template', 'create'],
['shift.template.update', 'template', 'update'],
```

Grant all `shift.*` to `SUPER_ADMIN` and `HR_MANAGER`.

## 8. Database Schema

### shift_templates table

```sql
CREATE TABLE shift_templates (
    id             UUID PRIMARY KEY,
    code           VARCHAR(50) NOT NULL UNIQUE,
    name           VARCHAR(255) NOT NULL,
    start_time     TIME NOT NULL,
    end_time       TIME NOT NULL,
    is_overnight   BOOLEAN NOT NULL DEFAULT FALSE,
    break_minutes  INT NOT NULL DEFAULT 0,
    late_tolerance_minutes INT NOT NULL DEFAULT 0,
    overtime_rules JSONB,
    flexibility_rules JSONB,
    payroll_attribution_rule VARCHAR(50) NULL,
    active         BOOLEAN NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP NOT NULL,
    updated_at     TIMESTAMP NOT NULL
);
```

### shift_assignments table

```sql
CREATE TABLE shift_assignments (
    id                UUID PRIMARY KEY,
    shift_template_id UUID NOT NULL REFERENCES shift_templates(id),
    assignable_type   VARCHAR(20) NOT NULL,
    assignable_id     UUID NOT NULL,
    effective_from    DATE NOT NULL,
    effective_to      DATE NULL,
    recurrence_rule   JSONB,
    active            BOOLEAN NOT NULL DEFAULT TRUE,
    created_at        TIMESTAMP NOT NULL,
    updated_at        TIMESTAMP NOT NULL
);

CREATE INDEX idx_shift_assignments_entity ON shift_assignments(assignable_type, assignable_id);
CREATE INDEX idx_shift_assignments_template ON shift_assignments(shift_template_id);
CREATE INDEX idx_shift_assignments_dates ON shift_assignments(effective_from, effective_to);
```

## 9. Testing Strategy

| Layer | Approach | Count |
|-------|----------|-------|
| Domain unit | Pure PHP: VO validation, overnight invariant, overlap detection, status machine | ~10 |
| Application | Handlers with fake repos | ~6 |
| Feature HTTP | Full API test + permission enforcement | ~12 |
| **Total** | | **~28** |

Key domain test cases:
- `ShiftTemplate::create()` with overnight=true missing attribution → throws
- `ShiftAssignment::assign()` with overlapping dates → throws
- `ShiftAssignment` dept assignment vs employee assignment resolution
- `ShiftWindow::duration()` correct
- `OvertimeRules` validation (negative values)

## 10. Acceptance Criteria

1. All 10+ API endpoints functional.
2. Shift template overnight invariant enforced (422).
3. Overlapping assignment prevented (422).
4. Employee-level assignment overrides department-level overlap resolution confirmed.
5. All shift.* permissions seeded.
6. HR_MANAGER role includes all shift.* permissions.
7. All tests pass.
8. Module structure matches Employee/Organization.

## 11. Implementation Order

1. Migration files (2: shift_templates, shift_assignments)
2. Eloquent models
3. Domain layer: VOs, aggregates, events, exceptions, repository interfaces
4. Application layer: commands/handlers + queries
5. Infrastructure persistence: eloquent repositories
6. HTTP layer: controllers, requests, resources, routes
7. Seeders: extend PermissionSeeder + RoleSeeder
8. Test suite: domain unit → application → feature
9. Module README

## 12. Dependencies

- **Identity module**: permission checks
- **Employee module**: validate assignable_id = valid EmployeeId
- **Organization module**: validate assignable_type=department → valid DepartmentId
- **Audit module**: event subscription through existing listener pattern

## 13. Risks

- Overlap resolution (dept assignment vs employee assignment) requires querying both levels — Phase 1 OK, small scale.
- JSONB columns for rules may need migration helpers in Phase 3 if rules become relational.
- First Phase 2 module — patterns may need adjustments for new BC interactions.
