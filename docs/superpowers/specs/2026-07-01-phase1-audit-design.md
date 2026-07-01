# Phase 1 Sub-3 Design — Audit & Activity Log

Version: 0.1
Date: 2026-07-01
Status: Design approved (brainstorming)

## 1. Scope

Build `app/Modules/Audit/` as the Phase 1 audit foundation for iHRM. The module records immutable audit logs for security, accountability, compliance, troubleshooting, and later Phase 1 modules.

In scope:

- `audit_logs` persistence model and migration.
- Event-driven audit capture for Identity module events.
- Normalized audit row writer with redaction.
- Read-only audit search API.
- `audit.log.list` permission and SUPER_ADMIN access.
- Feature/unit tests for audit writing, filtering, permissions, and redaction.

Out of scope:

- UI.
- Real-time streaming.
- Export.
- Audit log deletion/editing API.
- DB partitioning.
- Separate `activity_logs` table.
- Full integration with Organization, Employee, Contract, Document until those modules exist.

## 2. Architecture

Use event-driven audit capture.

Existing and future modules emit domain/application events. `AuditEventListener` maps those events into normalized `audit_logs` rows. Audit writes are best-effort: failures are logged but must not rollback the business transaction.

Dependency direction:

```text
Identity / Configuration / Future modules -> Laravel event bus -> Audit listener -> audit_logs
```

The Audit module provides only one public HTTP capability: read/search audit logs. It does not expose write/update/delete endpoints.

## 3. Module Layout

```text
src/backend/app/Modules/Audit/
  Application/
    Services/AuditLogger.php
  Domain/
    Events/AuditLogged.php
  Infrastructure/
    Listeners/AuditEventListener.php
    Persistence/
      Eloquent/AuditLogModel.php
    Http/
      Controllers/AuditLogController.php
      Resources/AuditLogResource.php
    Seeders/AuditPermissionSeeder.php
  Routes/api.php
```

Migration stays in Laravel's current project migration directory to match existing modules:

```text
src/backend/database/migrations/2026_07_01_200001_create_audit_logs_table.php
```

## 4. Data Model

`audit_logs` table:

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | Laravel UUID |
| `actor_user_id` | uuid nullable FK -> users.id | Nullable for failed login / system actions |
| `action` | string | `login`, `login_failed`, `logout`, `created`, `updated`, `disabled`, `reactivated`, `role_assigned`, `permission_granted`, etc. |
| `module` | string | `identity`, `configuration`, `organization`, `employee`, `contract`, `document`, `audit` |
| `entity_type` | string | `user`, `role`, `permission`, `employee`, `contract`, etc. |
| `entity_id` | string nullable | Target id when available |
| `before_payload` | json nullable | Safe changed values before action |
| `after_payload` | json nullable | Safe changed values after action |
| `ip_address` | string nullable | Request IP when available |
| `user_agent` | text nullable | Request UA when available |
| `result` | string | `success` or `failure` |
| `occurred_at` | timestamp | Business event time |
| `created_at`, `updated_at` | timestamps | Laravel timestamps |

Indexes:

- `actor_user_id, occurred_at`
- `module, entity_type, entity_id`
- `action`
- `result`
- `occurred_at`

Append-only rule is enforced by no public mutation endpoints and module convention. DB trigger protection is deferred until there is a concrete compliance requirement.

## 5. Event Mapping

Initial Identity events:

| Event | Action | Module | Entity Type | Result |
|---|---|---|---|---|
| `UserCreated` | `created` | `identity` | `user` | `success` |
| `UserLoggedIn` | `login` | `identity` | `user` | `success` |
| `UserLoginFailed` | `login_failed` | `identity` | `user` | `failure` |
| `UserDisabled` | `disabled` | `identity` | `user` | `success` |
| `UserReactivated` | `reactivated` | `identity` | `user` | `success` |
| `UserPasswordChanged` | `password_changed` | `identity` | `user` | `success` |
| `UserRoleAssigned` | `role_assigned` | `identity` | `user` | `success` |
| `UserRoleRevoked` | `role_revoked` | `identity` | `user` | `success` |
| `UserDataScopeGranted` | `data_scope_granted` | `identity` | `user` | `success` |
| `RoleCreated` | `created` | `identity` | `role` | `success` |
| `RoleUpdated` | `updated` | `identity` | `role` | `success` |
| `RolePermissionGranted` | `permission_granted` | `identity` | `role` | `success` |
| `RolePermissionRevoked` | `permission_revoked` | `identity` | `role` | `success` |

Future modules add events with the same normalized output:

- Organization: branch/department/position create/update/delete/status changes.
- Employee: profile create/update/status changes.
- Contract: create/update/status/renewal changes.
- Document: upload/download/replace/delete/expiry changes.
- Configuration: lookup/setting/code-rule/holiday/threshold changes.

## 6. Payload and Redaction

Payloads are optional. Events that include before/after snapshots should pass safe values only. `AuditLogger` still redacts sensitive keys defensively.

Redacted key names, case-insensitive:

- `password`
- `password_hash`
- `token`
- `access_token`
- `refresh_token`
- `secret`
- `api_key`

Nested arrays are recursively redacted.

Actor/request metadata source order:

1. Event actor id, if present.
2. `auth()->id()`.
3. `null`.

IP and user agent come from the current request when available.

## 7. HTTP API

Endpoint:

```http
GET /api/v1/audit-logs
```

Middleware:

```text
auth:sanctum
permission:audit.log.list
```

Filters:

- `actor_user_id`
- `action`
- `module`
- `entity_type`
- `entity_id`
- `result`
- `date_from`
- `date_to`
- `per_page`

Response shape:

```json
{
  "data": [
    {
      "id": "uuid",
      "actor_user_id": "uuid-or-null",
      "action": "login",
      "module": "identity",
      "entity_type": "user",
      "entity_id": "uuid-or-null",
      "before_payload": null,
      "after_payload": {"email": "admin@ihrm.local"},
      "ip_address": "127.0.0.1",
      "user_agent": "...",
      "result": "success",
      "occurred_at": "2026-07-01T00:00:00Z"
    }
  ],
  "meta": {}
}
```

## 8. Permissions and Seed Data

Add permission:

```text
audit.log.list
```

Use the existing Identity permission seeding pattern. SUPER_ADMIN receives it through the existing RoleSeeder behavior that grants all active permissions.

## 9. Error Handling

- Audit listener catches write failures.
- Failures are logged through Laravel logger.
- Business operation is not rolled back by audit failure.
- API validation errors use existing shared error response behavior.

## 10. Tests

Unit tests:

- `AuditLoggerTest` writes an audit row.
- Redaction removes sensitive nested keys.
- Listener maps representative Identity events to normalized audit rows.

Feature tests:

- Admin can list audit logs.
- User without `audit.log.list` gets `403`.
- Filters by actor/action/module/entity/date/result work.
- Login creates a success audit log.
- Failed login creates a failure audit log.
- Role permission changes create audit logs.

Full verification:

```bash
docker compose run --rm app php artisan test tests/Unit/Modules/Audit tests/Feature/Modules/Audit
docker compose run --rm app php artisan test
```

## 11. Acceptance Criteria

- `audit_logs` table exists with required fields and indexes.
- Audit module follows Identity module layout conventions.
- `GET /api/v1/audit-logs` returns paginated results and honors filters.
- `audit.log.list` permission is seeded and SUPER_ADMIN can access audit logs.
- Identity login, failed login, role assignment, role revocation, and permission grant/revoke produce audit rows.
- Sensitive values are redacted from payloads.
- Full backend test suite passes.
