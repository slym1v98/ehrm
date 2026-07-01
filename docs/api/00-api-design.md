# API Design Overview

Version: 0.1  
Date: 2026-06-30  
Status: Draft for review

## 1. Purpose

This document defines the API design conventions for the iHRM platform. Detailed OpenAPI 3.1 specs live in `docs/api/openapi/01-core-platform.openapi.yaml` through `docs/api/openapi/04-enterprise-extensions.openapi.yaml`.

## 2. Design Principles

- API-first for Laravel 12 backend and NextJS frontend.
- REST resource-first endpoints; command-style subresources for business actions.
- Stable, boring JSON envelopes.
- Authorization at backend via permission + data-scope policy.
- Async job endpoints for long-running work.
- OpenAPI 3.1 YAML as source of truth.

## 3. Versioning

- Base path prefix: `/api/v1`.
- Breaking changes require `/api/v2`.
- Backward-compatible additions stay inside `v1`.

## 4. Authentication and Authorization

### 4.1 Security Schemes

- `BearerAuth`: bearer token for SPA/mobile/API clients.
- `CookieAuth` is not part of the primary v1 contract; it remains an optional future SSR adapter.

### 4.2 Authorization Model

- Access tokens carry only `user_id`; permissions and data scopes are resolved server-side per request.

- Permission naming: `{module}.{action}`.
- Data scope is evaluated separately: `self`, `direct_reports`, `department`, `branch`, `all_company`.
- `401` = unauthenticated.
- `403` = authenticated but lacks permission or exceeds data scope.

## 5. Resource and Command Patterns

Resource-first examples:

- `GET /api/v1/employees`
- `POST /api/v1/leave-requests`
- `GET /api/v1/payroll-runs/{id}`

Command-style examples:

- `POST /api/v1/workflow-requests/{id}/approve`
- `POST /api/v1/payroll-periods/{id}/lock`
- `POST /api/v1/employees/{id}/status-changes`

## 6. Common Response Envelopes

### 6.1 Success

```json
{
  "data": {},
  "meta": {},
  "links": {}
}
```

### 6.2 Error

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": [
      {"field": "employee_code", "message": "Already exists"}
    ],
    "trace_id": "uuid"
  }
}
```

## 7. Pagination, Filtering, Sorting

### 7.1 Query Pattern

`GET /api/v1/employees?page=1&per_page=20&sort=-created_at&filter[status]=active`

### 7.2 Paginated Response

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

## 8. Async Job Pattern

Used for payroll, reporting, analytics, archive, integration.

### 8.1 Start Job

`POST /api/v1/payroll-runs`

Returns `202 Accepted`:

```json
{
  "data": {
    "job_id": "uuid",
    "status": "queued",
    "status_url": "/api/v1/jobs/uuid"
  }
}
```

### 8.2 Poll Job

`GET /api/v1/jobs/uuid`

```json
{
  "data": {
    "job_id": "uuid",
    "status": "completed",
    "result_url": "/api/v1/payroll-runs/123"
  }
}
```

## 9. File Upload and Download Pattern

- Uploads use `multipart/form-data`.
- File metadata stored in DB.
- Files stored privately in MinIO.
- Downloads via authorized endpoint that returns presigned URL or streams file.

Examples:

- `POST /api/v1/employee-documents`
- `GET /api/v1/employee-documents/{id}/download`

## 10. Standard Status Codes

- `200 OK`
- `201 Created`
- `202 Accepted`
- `204 No Content`
- `400 Bad Request`
- `401 Unauthorized`
- `403 Forbidden`
- `404 Not Found`
- `409 Conflict`
- `422 Unprocessable Entity`
- `429 Too Many Requests`
- `500 Internal Server Error`
- `502 Bad Gateway`
- `503 Service Unavailable`

## 11. Idempotency and Concurrency

- Safe retries for creation endpoints may use `Idempotency-Key` header where valuable (payments not in scope, but payroll/integration/report requests may benefit).
- Resource versioning/optimistic locking can be added later for high-conflict aggregates.
- Lock operations (payroll lock, attendance close) are server-authoritative.

## 12. Naming Conventions

- Paths: plural nouns, kebab-case.
- JSON fields: snake_case.
- Enumerations: lowercase snake_case strings.
- Date/time: ISO 8601.
- UUIDs everywhere for public ids.

## 13. Traceability

- Endpoints map to Use Cases.
- Request/response schemas map to DDD aggregates or query models.
- Validation and security rules map to SRS.
- Resource ownership maps to ERD tables.
