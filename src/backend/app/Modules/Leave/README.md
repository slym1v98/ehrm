# Leave Module

Phase 2 Leave BC. Inline approval only; no Workflow BC, notification delivery, accrual scheduler, or Attendance table mutation.

## Aggregates

- `LeaveType`: active catalog item; balance-tracked flag.
- `LeavePolicy`: date-valid duration rules.
- `LeaveRequest`: `pending -> approved|rejected|cancelled`; approved cancellation restores balance.
- `LeaveBalance`: `remaining = opening + accrued - used - expired - carried_over`.

## API

All routes are under `/api/v1` with `auth:sanctum` + permission middleware.

- `GET /leave-types` → `leave.type.view`
- `GET /leave-policies` → `leave.policy.view`
- `POST /leave-requests` → `leave.request.create`
- `GET /leave-requests` → `leave.request.view`
- `GET /leave-requests/{id}` → `leave.request.view`
- `POST /leave-requests/{id}/approve` → `leave.request.approve`
- `POST /leave-requests/{id}/reject` → `leave.request.reject`
- `POST /leave-requests/{id}/cancel` → `leave.request.cancel`
- `GET /leave-balances` → `leave.balance.view`
- `GET /leave-balances/summary` → `leave.balance.view`

## Read Side

`LeaveWindowInterface` returns approved `LeavePeriod` windows for Attendance consumers.

## Validation

```bash
docker compose run --rm app php artisan migrate:fresh --seed
docker compose run --rm app php artisan test tests/Unit/Modules/Leave tests/Feature/Modules/Leave --compact
docker compose run --rm app php artisan test --compact
```
