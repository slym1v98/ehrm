# Phase 2 Leave BC Design

Version: 0.1
Date: 2026-07-02
Status: Design approved (brainstorming)

## 1. Scope

Build Leave module (`app/Modules/Leave/`) as the third Phase 2 module. Covers leave types, policies, requests with inline approval, and balance tracking.

**In scope:** `LeaveType` catalog (annual, sick, unpaid, maternity, company-specific), `LeavePolicy` (accrual rules, carry-forward, expiry, half-day/hourly), `LeaveRequest` submit/approve/reject/cancel with inline `status`/`approved_by`, `LeaveBalance` deduction/restoration (opening + accrued - used + carry-over), Employee/Configuration lookups, basic overlap/balance/policy validation, permission integration with Identity module, full test suite.

**Out of scope:** Workflow BC integration (approval uses inline `approved_by`/`approved_at`; migrating to Workflow BC deferred), notification (event-driven but no delivery in this module), payroll consumption (Leave BC provides a `LeaveWindowInterface` for Attendance; Payroll reads from finalized attendance), scheduled accrual jobs (balance seeded or manually adjusted), attachments/evidence files, multi-company calendar integration (company-level leave policies handled through Configuration BC), attendance mutation (leave is a read-only input for Attendance).

## 2. Architecture

Strict DDD tactical pattern with 3 layers, mirroring Attendance/Shift modules.

```
Module/Leave/
  Domain/         — Pure PHP, no Laravel deps
  Application/    — Commands/Handlers + Queries
  Infrastructure/ — Eloquent, HTTP controllers, seeders, routes
```

**Dependency:** Domain ← Application ← Infrastructure.

**Key difference from Attendance:** LeaveRequest uses inline approval fields (`status`, `approved_by`, `approved_at`, `rejected_reason`) instead of a `WorkflowRequest`. Balance deduction occurs on approval; restoration on approved cancellation.

## 3. Module Layout

```
app/Modules/Leave/
  Domain/
    Aggregates/
      LeaveType/LeaveType.php, LeaveTypeId.php
      LeavePolicy/LeavePolicy.php, LeavePolicyId.php
      LeaveRequest/LeaveRequest.php, LeaveRequestId.php
      LeaveBalance/LeaveBalance.php, LeaveBalanceId.php
    ValueObjects/
      DurationUnit.php           — enum: day|half_day|hour
      LeaveStatus.php            — enum: pending|approved|rejected|cancelled
      LeavePeriod.php            — startAt(immutableDate), endAt(immutableDate), durationMinutes(int)
    Events/
      LeaveRequestSubmitted.php
      LeaveRequestApproved.php
      LeaveRequestRejected.php
      LeaveRequestCancelled.php
      LeaveBalanceAdjusted.php
    Repositories/
      LeaveTypeRepositoryInterface.php
      LeavePolicyRepositoryInterface.php
      LeaveRequestRepositoryInterface.php
      LeaveBalanceRepositoryInterface.php
    Exceptions/
      OverlappingLeaveException.php
      InsufficientBalanceException.php
      LeaveTypeNotFoundException.php
      LeavePolicyNotFoundException.php
      LeaveRequestNotFoundException.php
      LeaveBalanceNotFoundException.php
      InvalidLeaveStatusTransitionException.php
  Application/
    Commands/LeaveRequest/
      SubmitLeaveRequestCommand.php
      ApproveLeaveRequestCommand.php
      RejectLeaveRequestCommand.php
      CancelLeaveRequestCommand.php
    CommandHandlers/LeaveRequest/
      SubmitLeaveRequestHandler.php
      ApproveLeaveRequestHandler.php
      RejectLeaveRequestHandler.php
      CancelLeaveRequestHandler.php
    Queries/LeaveRequest/
      GetLeaveRequestQuery.php
      ListLeaveRequestsQuery.php
      GetEmployeeLeaveBalanceQuery.php
    QueryHandlers/
      GetLeaveRequestHandler.php
      ListLeaveRequestsHandler.php
      GetEmployeeLeaveBalanceHandler.php
  Infrastructure/
    Persistence/
      Eloquent/
        LeaveTypeModel.php
        LeavePolicyModel.php
        LeaveRequestModel.php
        LeaveBalanceModel.php
      Repositories/
        EloquentLeaveTypeRepository.php
        EloquentLeavePolicyRepository.php
        EloquentLeaveRequestRepository.php
        EloquentLeaveBalanceRepository.php
    Http/
      Controllers/
        LeaveRequestController.php
        LeaveTypeController.php
        LeavePolicyController.php
        LeaveBalanceController.php
      Requests/
        SubmitLeaveRequest.php
        ApproveLeaveRequest.php
        RejectLeaveRequest.php
        CancelLeaveRequest.php
      Resources/
        LeaveRequestResource.php
        LeaveTypeResource.php
        LeavePolicyResource.php
        LeaveBalanceResource.php
    Seeders/
      LeaveTypeSeeder.php
```

## 4. Schema

### `leave_types`

| Column | Type | Notes |
|---|---|---|
| id | UUID | PK |
| name | varchar(255) | "Annual Leave", "Sick Leave" |
| code | varchar(50) unique | `annual`, `sick`, `unpaid`, `maternity` |
| is_balance_tracked | boolean | true for annual, maternity; false for sick, unpaid |
| is_active | boolean | default true |
| sort_order | integer | display ordering |
| created_at | timestamp | |
| updated_at | timestamp | |

### `leave_policies`

| Column | Type | Notes |
|---|---|---|
| id | UUID | PK |
| leave_type_id | UUID | FK → leave_types |
| valid_from | date | policy effective start |
| valid_until | date | nullable |
| max_consecutive_days | integer | maximum days per request |
| requires_attachment | boolean | |
| carry_over_limit | integer | nullable |
| carry_over_expiry_months | integer | nullable |
| half_day_allowed | boolean | |
| hourly_allowed | boolean | |
| created_at | timestamp | |
| updated_at | timestamp | |

### `leave_requests`

| Column | Type | Notes |
|---|---|---|
| id | UUID | PK |
| employee_id | UUID | FK → employees |
| leave_type_id | UUID | FK → leave_types |
| start_at | date | inclusive |
| end_at | date | inclusive |
| duration_unit | varchar(20) | day|half_day|hour |
| duration_minutes | integer | calculated |
| reason | text | nullable |
| status | varchar(20) | pending|approved|rejected|cancelled |
| approved_by | UUID | nullable, FK → users |
| approved_at | timestamp | nullable |
| rejected_reason | text | nullable |
| balance_before | integer | nullable, snapshot of `used` before deduction |
| created_at | timestamp | |
| updated_at | timestamp | |

Unique constraint: unique(employee_id, leave_type_id, start_at, end_at, status) where status IN (pending, approved).

### `leave_balances`

| Column | Type | Notes |
|---|---|---|
| id | UUID | PK |
| employee_id | UUID | FK → employees |
| leave_type_id | UUID | FK → leave_types |
| year | integer | 2026, 2027... |
| opening | integer | carried into this year |
| accrued | integer | earned this year |
| used | integer | consumed this year |
| carried_over | integer | to next year |
| expired | integer | forfeited |
| created_at | timestamp | |
| updated_at | timestamp | |

Unique constraint: unique(employee_id, leave_type_id, year).

Remaining computed as: `remaining = opening + accrued - used - expired - carried_over`.

## 5. Domain Model

### Aggregates

#### LeaveType

- `id: LeaveTypeId`
- `name: string`
- `code: string`
- `isBalanceTracked: bool`
- `isActive: bool`
- `sortOrder: int`
- Methods: `activate()`, `deactivate()`

#### LeavePolicy

- `id: LeavePolicyId`
- `leaveTypeId: LeaveTypeId`
- `validFrom: Date`, `validUntil: ?Date`
- `maxConsecutiveDays: int`
- `requiresAttachment: bool`
- `carryOverLimit: ?int`, `carryOverExpiryMonths: ?int`
- `halfDayAllowed: bool`, `hourlyAllowed: bool`
- Methods: `isValidForDate(date)` — checks effective range; `allowsDuration(unit)` — checks half-day/hour policy.

#### LeaveRequest

State machine:

```
  pending ──→ approved
  pending ──→ rejected
  pending ──→ cancelled     (self-cancel before approval)
  approved ─→ cancelled     (within cancellation window, restores balance)
```

- `id: LeaveRequestId`
- `employeeId: EmployeeId`
- `leaveTypeId: LeaveTypeId`
- `period: LeavePeriod` (startAt, endAt, durationMinutes)
- `durationUnit: DurationUnit`
- `reason: ?string`
- `status: LeaveStatus`
- `approvedBy: ?UserId`, `approvedAt: ?DateTime`, `rejectedReason: ?string`
- `balanceBefore: ?int` — snapshot of `LeaveBalance.used` before deduction
- Methods: `submit()`, `approve(userId)`, `reject(userId, reason?)`, `cancel(userId)`
  - `approve()` guards: status == pending, sets approvedBy/At, returns `LeaveRequestApproved` event.
  - `reject()` guards: status == pending, sets rejectedReason, returns `LeaveRequestRejected` event.
  - `cancel()` guards: status == pending or (status == approved && within cancellation window defined by LeavePolicy). On approved-cancel, also returns `LeaveBalanceAdjusted` event (restore).

Guard methods: `isOverlapping(otherRequests)` — called externally from handler.

#### LeaveBalance

- `id: LeaveBalanceId`
- `employeeId: EmployeeId`
- `leaveTypeId: LeaveTypeId`
- `year: int`
- `opening, accrued, used, carriedOver, expired: int`
- Read-only computed: `remaining(): int`
- Methods: `deduct(minutes)` → guard `remaining >= minutes`, sets `used += minutes`, returns `LeaveBalanceAdjusted`. `restore(minutes)` → `used -= minutes`. Both emit event.

### Value Objects

**DurationUnit:** enum `day|half_day|hour`. Minutes mapping: day=480, half_day=240, hour=60 (configurable through Configuration BC in future).

**LeaveStatus:** enum `pending|approved|rejected|cancelled`.

**LeavePeriod:** `startAt: DateImmutable`, `endAt: DateImmutable`, `durationMinutes: int` — computed from unit + calendar days.

### Events

- `LeaveRequestSubmitted` — { leaveRequestId, employeeId, leaveTypeId, period }
- `LeaveRequestApproved` — { leaveRequestId, approvedBy, approvedAt }
- `LeaveRequestRejected` — { leaveRequestId, rejectedBy, reason }
- `LeaveRequestCancelled` — { leaveRequestId, cancelledBy }
- `LeaveBalanceAdjusted` — { balanceId, employeeId, leaveTypeId, year, previousUsed, newUsed }

Events dispatched via Laravel event system; Audit module subscribes through existing listener pattern.

### Exceptions

- `OverlappingLeaveException` → 409
- `InsufficientBalanceException` → 422
- `LeaveTypeNotFoundException` → 404
- `LeavePolicyNotFoundException` → 404
- `LeaveRequestNotFoundException` → 404
- `LeaveBalanceNotFoundException` → 404
- `InvalidLeaveStatusTransitionException` → 422

### Repository Interfaces

- `LeaveTypeRepositoryInterface`: `findById(id)`, `findByCode(code)`, `all()`, `save(type)`
- `LeavePolicyRepositoryInterface`: `findById(id)`, `findByType(typeId, date)`, `save(policy)`
- `LeaveRequestRepositoryInterface`: `findById(id)`, `findOverlapping(employeeId, start, end, excludeId?)`, `findByEmployee(employeeId, filters)`, `save(request)`
- `LeaveBalanceRepositoryInterface`: `findByEmployeeTypeYear(employeeId, typeId, year)`, `save(balance)`

## 6. Application Layer

### Commands & Handlers

| Command | Handler | Validates |
|---|---|---|
| `SubmitLeaveRequest` | `SubmitLeaveRequestHandler` | leave type active → policy valid → overlap check → balance check → save → dispatch event |
| `ApproveLeaveRequest` | `ApproveLeaveRequestHandler` | request exists → status pending → deduct balance → mark approved → save → dispatch event |
| `RejectLeaveRequest` | `RejectLeaveRequestHandler` | request exists → status pending → mark rejected → save → dispatch event |
| `CancelLeaveRequest` | `CancelLeaveRequestHandler` | request exists → status allowed → restore balance (if approved) → save → dispatch event |

### Queries

| Query | Returns |
|---|---|
| `ListLeaveRequestsQuery` | paginated, filterable by employee_id, status, leave_type_id, date_range |
| `GetLeaveRequestQuery` | single request detail |
| `GetEmployeeLeaveBalanceQuery` | balance by employee + type OR all balances for an employee |

### Attendance Integration (read side)

Expose a `LeaveWindowInterface` in the Domain layer:

```php
interface LeaveWindowInterface {
    /** @return LeavePeriod[] covering date range */
    public function getLeaveWindows(EmployeeId $employeeId, DateRange $range): array;
}
```

Infrastructure implements via Eloquent query on `leave_requests` where `status = approved`. AttendanceCalculator receives this interface as constructor dependency; when Leave BC exists it gets real data, otherwise empty collection.

## 7. HTTP Layer

### Endpoints

All under `Route::prefix('v1')->middleware('auth:sanctum')`:

| Method | Path | Permission | Handler |
|---|---|---|---|
| GET | `/leave-types` | `leave.type.view` | `LeaveTypeController@index` |
| POST | `/leave-requests` | `leave.request.create` | `LeaveRequestController@store` |
| GET | `/leave-requests` | `leave.request.view` | `LeaveRequestController@index` |
| GET | `/leave-requests/{id}` | `leave.request.view` | `LeaveRequestController@show` |
| POST | `/leave-requests/{id}/approve` | `leave.request.approve` | `LeaveRequestController@approve` |
| POST | `/leave-requests/{id}/reject` | `leave.request.reject` | `LeaveRequestController@reject` |
| POST | `/leave-requests/{id}/cancel` | `leave.request.cancel` | `LeaveRequestController@cancel` |
| GET | `/leave-balances` | `leave.balance.view` | `LeaveBalanceController@index` |
| GET | `/leave-balances/summary` | `leave.balance.view` | `LeaveBalanceController@summary` |
| GET | `/leave-policies` | `leave.policy.view` | `LeavePolicyController@index` |

### Form Requests

`SubmitLeaveRequest`: validate `leave_type_id` (exists, active), `start_at`/`end_at` (required, date, end >= start), `duration_unit` (in: day, half_day, hour), `reason` (max: 1000).

`ApproveLeaveRequest`: no body needed.

`RejectLeaveRequest`: `reason` (required, max: 1000).

`CancelLeaveRequest`: no body needed.

### Responses

`LeaveRequestResource`: id, employee_id, leave_type_id, start_at, end_at, duration_unit, duration_minutes, reason, status, approved_by, approved_at, rejected_reason, created_at, updated_at.

`LeaveTypeResource`: id, name, code, is_balance_tracked, is_active, sort_order.

`LeaveBalanceResource`: id, employee_id, leave_type_id, year, opening, accrued, used, carried_over, expired, remaining (computed).

### Error Handling

| Exception | HTTP Code |
|---|---|
| `OverlappingLeaveException` | 409 |
| `InsufficientBalanceException` | 422 |
| `NotFoundException` subclasses | 404 |
| `InvalidLeaveStatusTransitionException` | 422 |
| Authorization (Sanctum) | 401 |
| Permission denied | 403 |

## 8. Permissions

```php
['leave.type.view',          'leave-type',     'view'],
['leave.policy.view',        'leave-policy',   'view'],
['leave.request.create',     'leave-request',  'create'],
['leave.request.view',       'leave-request',  'view'],
['leave.request.approve',    'leave-request',  'approve'],
['leave.request.reject',     'leave-request',  'reject'],
['leave.request.cancel',     'leave-request',  'cancel'],
['leave.balance.view',       'leave-balance',  'view'],
```

Grant all `leave.*` to `SUPER_ADMIN` and `HR_MANAGER`. View permissions self-scoped for employees (can see own requests/balances); create/approve/reject scoped by role.

## 9. Testing Strategy

| Layer | Approach | Est. Count |
|---|---|---|
| Domain unit | LeaveType/LeavePolicy VO validation, LeaveRequest state machine (submit/approve/reject/cancel/illegal transitions), LeaveBalance deduct/restore/overflow, overlap detection, policy compliance | ~15 |
| Application | Handlers with fake repos: submit (success, overlap, balance), approve (deduct, already-approved, not-found), reject, cancel (pending, approved-with-restore), list queries | ~10 |
| Feature HTTP | Full API + permission enforcement (401/403) across all endpoints + status transition scenarios | ~8 |
| **Total** | | **~33** |

Key test cases for LeaveRequest state machine:

- Submit: creates pending request, overlap rejected 409, insufficient balance rejected 422, policy max_consecutive_days exceeded 422.
- Approve: pending→approved, balance deducted, already-rejected→422.
- Reject: pending→rejected, requires reason, already-approved→422.
- Cancel: pending→cancelled (no balance impact), approved→cancelled (balance restored), already-cancelled→422.
- Balance: opening+accrued-used computation, deduction guard, restore on approved-cancel.
- Attendance integration: `LeaveWindowInterface` returns correct windows for approved requests.

## 10. Acceptance Criteria

1. Leave types can be listed via GET endpoint.
2. Leave policies can be listed; policy validation applied on request submission.
3. Leave request submission validates: active type, valid policy, overlap, balance (if tracked) → 409/422 on violation, 201 on success.
4. Leave request approval transitions `pending → approved` and deducts balance.
5. Leave request rejection transitions `pending → rejected` with required reason.
6. Leave request cancellation: pending → cancelled (no balance change), approved → cancelled (balance restored).
7. Illegal transitions (approved → approve, rejected → approve) → 422.
8. Leave balance computed as opening + accrued - used - expired - carried_over.
9. `LeaveWindowInterface` returns approved leave periods for a given employee + date range.
10. All `leave.*` permissions seeded; SUPER_ADMIN and HR_MANAGER roles grant them.
11. All Leave tests pass; full backend suite green.
12. Module structure mirrors Attendance / Shift (Domain / Application / Infrastructure).

## 11. Dependencies

- **Identity** — permission checks, seeder extension.
- **Employee** — validates `employee_id` values.
- **Configuration** — working calendar reads (basic; empty collection acceptable initially).
- **Audit** — subscribes to Leave events through existing listener pattern.

## 12. Implementation Order

1. Migrations (4 tables: leave_types, leave_policies, leave_requests, leave_balances).
2. Eloquent models with UUID keys and date/carbon casts.
3. Domain layer: value objects → events → exceptions → aggregates → repository interfaces.
4. Repository implementations + DI bindings in AppServiceProvider.
5. Application layer: commands, handlers, queries, query handlers.
6. HTTP layer: FormRequests, resources, controllers, module routes, wire into routes/api.php.
7. Seeders: LeaveTypeSeeder (annual, sick, unpaid, maternity); extend PermissionSeeder and RoleSeeder in Identity module.
8. Test suite: domain unit → application → feature HTTP.
9. Module README with aggregates, endpoints, permissions, test commands.
10. Attendance integration pass: wire `LeaveWindowInterface`.

## 13. Self-Review Checklist

- ✅ Placeholder scan: no TBD/TODO/implement-later placeholders.
- ✅ Internal consistency: architecture matches DDD pattern, names match paths.
- ✅ Scope check: focused on Leave BC only; Workflow/Notifications deferred.
- ✅ Ambiguity check: status machine, balance logic, period boundaries defined.
- ✅ YAGNI: no Workflow, no attachments, no attachment storage, no accrual scheduler, no notification delivery, no payroll mutation.

## 14. Risks

- **Overlap check performance:** `findOverlapping` query on `leave_requests` for (employee_id, date range, status IN pending/approved) — indexed by employee + date range + status. Fine for current scale; composite index added.
- **Balance race condition:** Concurrent approve + approve on same balance. Mitigated by application-layer transaction (Pessimistic locking deferred until contention proves need).
- **Deadline for cancellation:** Approved cancellation window is a policy field. Currently not enforced by domain but by handler. Move to aggregate guard if business requires strict enforcement.
- **LeaveType seeder tight coupling:** Seeders create fixed codes; company-specific types require manual insert. No admin CRUD for types in Phase 2; deferred.
