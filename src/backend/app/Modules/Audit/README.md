# Audit Module

Read-only Audit & Activity Log module for Phase 1.

## Responsibilities

- Persist append-only audit rows in `audit_logs`.
- Capture Identity events through `AuditEventListener`.
- Redact sensitive keys before persistence.
- Expose `GET /api/v1/audit-logs` for authorized users.

## Permission

- `audit.log.list` — list and filter audit logs.

## Test commands

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Audit tests/Feature/Modules/Audit
docker compose run --rm app php artisan test
```
