# Technical Design Overview

Version: 0.1  
Date: 2026-06-30  
Status: Draft for review

## 1. Purpose

This document defines the technical architecture and runtime model for the iHRM platform. Detailed per-phase technical design lives in:

- `01-core-platform-tech.md`
- `02-workforce-ops-tech.md`
- `03-talent-lifecycle-tech.md`
- `04-enterprise-extensions-tech.md`

## 2. Tech Stack

- Backend: Laravel 12 / PHP 8.4, API-only
- Frontend: NextJS (App Router), TypeScript
- Database: PostgreSQL 16
- Cache/queue/session/lock/rate-limit: Redis 7
- File storage: MinIO private bucket (S3-compatible)
- Container: Docker Compose for dev/staging, Kubernetes optional for prod

## 3. Architecture Style

- Modular Monolith with DDD/Clean Architecture per module
- API-first; frontend never directly queries the database
- Cross-module communication via domain events
- One-aggregate-per-transaction default
- Async work via queue jobs
- File uploads commit metadata only after storage success

## 4. Runtime Components

- Laravel HTTP API
- Laravel queue worker
- Laravel scheduler (cron)
- NextJS web app (CSR primary, optional SSR for SEO/portals)
- PostgreSQL primary DB
- Redis cache/queue/session
- MinIO object storage
- Nginx/HTTP load balancer in front of Laravel and NextJS

## 5. Environment Topology

- Local: docker-compose up
- Staging: single cluster with seeded data
- Production: HA setup, multi-replica queue worker, database failover (target shape documented but not required for Phase 1)

## 6. Cross-Cutting Concerns

### 6.1 Logging

- JSON structured logs
- Log levels per environment
- Central log aggregation in staging/prod (e.g., Loki, ELK) — out of scope to vendor choice in this design

### 6.2 Observability

- Application logs
- Queue/job metrics (processed, failed, retries, runtime)
- HTTP request logs
- Optional OpenTelemetry traces for critical paths

### 6.3 Security

- TLS termination at edge
- Bearer token auth via Sanctum
- RBAC + data scope enforced at middleware/policy
- PII masking in API responses
- Private file storage with presigned URLs
- Audit log for material changes
- Secrets via environment or secret manager (not committed)

### 6.4 Error Handling

- Standard error envelope per `00-api-design.md`
- Global exception handler maps domain errors to HTTP responses
- Domain errors have stable codes

### 6.5 Idempotency

- `Idempotency-Key` header support for selected create endpoints (Phase 2+)
- DB unique constraints for natural idempotency on data

## 7. CI/CD Outline

- Lint and static analysis
- Domain unit tests (no Laravel boot)
- Application tests (fake repositories and event bus)
- Feature/integration tests (real DB, Redis, MinIO)
- API contract tests against OpenAPI
- Migration dry-run on staging
- Deploy to dev/staging, run smoke tests

## 8. Testing Strategy

- Domain: pure PHP, in-memory repos
- Application: use case unit tests with fakes
- Infrastructure: integration tests with real DB/Redis/MinIO
- HTTP: Laravel feature tests + API contract tests
- Performance: smoke tests for heavy endpoints

## 9. Performance and Scale Targets

- API read p95: <500ms
- API write p95: <1000ms
- Payroll run: async, progress visible
- Attendance raw log write: high-volume, no blocking

## 10. Non-Goals for Phase 1

- Microservices decomposition
- Multi-tenant SaaS
- Event sourcing
- Full BPMN engine
- Mobile native apps (mobile API ready only)

## 11. Out of Scope

- Specific cloud provider choice
- Specific managed DB choice
- Vendor selection for monitoring/observability
