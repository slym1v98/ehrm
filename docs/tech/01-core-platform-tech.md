# Phase 1 Technical Design — Core Platform

Version: 0.1  
Date: 2026-06-30  
Status: Draft for review

## 1. Scope

Covers Identity & Access, Organization, Employee Master, Contract, Document, Configuration, and Audit.

## 2. Modules

```text
app/Modules/
  Identity/        # Users, roles, permissions, data scope, login
  Organization/    # Branches, departments, positions
  Employee/        # Employees, contracts, documents, history
  Configuration/   # Lookups, code generation, settings
  Audit/           # Audit logs (read-only)
```

## 3. Persistence Strategy

- PostgreSQL primary
- Migrations live in each module's `Database/Migrations/`
- Migration order enforced by module dependency
- Soft delete only on tables where reversible deletion is a business requirement
- UUIDs as primary keys
- Effective-date columns: `effective_from`, `effective_to` where temporal data

## 4. Seeders

- Default permissions
- Default roles
- Lookup groups and values
- Default system settings
- Default code generation rules

## 5. Auth and Authorization

- Sanctum token issuance via `POST /api/v1/auth/login`
- Token payload carries user identity; permission/data-scope resolved per request
- Middleware:
  - `auth:sanctum` for protected routes
  - `permission:{code}` for permission checks
  - `data-scope:{type}` for data scope filtering
- Policies at module level for ownership checks

## 6. Audit Pattern

- Application or domain layer publishes events
- `AuditEventListener` (Phase 1 BC) subscribes to events
- Writes `audit_logs` with before/after payload
- Audit writes are best-effort but failures are logged

## 7. File Storage

- MinIO private bucket
- `FileObject` entity stores descriptor (bucket, key, checksum, mime, size)
- Upload flow:
  - Backend issues presigned PUT or streams to MinIO
  - Metadata written only after upload success
- Download flow:
  - Authorized endpoint returns presigned URL
  - Alternative: streamed download after permission check

## 8. Cache Strategy

- `Cache::tags` for module-scoped invalidation
- Employee lookup cache invalidated on Employee update
- Permission/data scope not cached initially; revisit when proven hot

## 9. Queues and Jobs

- Phase 1 has minimal queue usage
- Document scanning/validation could be async
- Audit log fan-out runs in listener; should not block request thread

## 10. Events

- Domain events per aggregate
- App events for cross-module signals
- Event bus: Laravel `event()` and `Event::dispatch`
- Listeners registered per module

## 11. Test Strategy

- Domain unit tests: pure PHP, in-memory repos
- Application tests: use case flow with fakes
- Feature tests: full HTTP against real DB/MinIO
- API contract tests: validate request/response shape against OpenAPI

## 12. Performance Targets

- Employee list p95: <500ms
- Employee detail p95: <500ms
- Document upload: large file, time not bounded by metadata path
- Audit log search: indexed filters, pagination

## 13. Security

- PII fields masked in API responses unless caller has unmask permission
- Document downloads audited
- All material changes audited
- Rate limiting on auth endpoints

## 14. Boundaries

- No direct mutation of Employee from other modules
- No direct mutation of Organization from other modules
- Identity references Employee by id only


## 15. Company Singleton

Phase 1 treats company profile as singleton configuration, not a full Company aggregate/table. This keeps the single-tenant scope explicit and avoids premature multi-company modeling.
