# Consistency Review — 2026-06-30

Version: 0.1  
Date: 2026-06-30  
Status: Review findings

## 1. Executive Summary

Reviewed packs:

- `docs/srs/*`
- `docs/superpowers/specs/*` (DDD)
- `docs/erd/*`
- `docs/usecase/*`
- `docs/api/*`
- `docs/tech/*`

Overall assessment:

- **Architecture direction is coherent**: single-tenant, modular monolith, Laravel 12 API-only, NextJS, PostgreSQL, Redis, MinIO.
- **Phase decomposition is coherent**: Core Platform → Workforce Ops → Talent Lifecycle → Enterprise Extensions.
- **Traceability mostly works** across SRS → DDD → ERD → Use Case → API → Tech.
- **Main remaining issues are not conceptual contradictions**, but **contract gaps and a few terminology/ownership drifts**.

Severity summary:

- High: 4
- Medium: 7
- Low: 5

## 2. Findings Table

| ID | Severity | Area | Files | Finding | Suggested Fix |
| --- | --- | --- | --- | --- | --- |
| F-001 | High | API coverage gap | `docs/usecase/02-workforce-ops-uc.md`, `docs/api/openapi/02-workforce-ops.openapi.yaml` | Use cases define `Approve Payroll`, `Publish Payslips`, `Adjust Payroll Entry`, `Create Workflow Template`, `Configure Leave Policy`, but OpenAPI Phase 2 does not expose endpoints for all of them. | Add missing Phase 2 endpoints or explicitly mark them deferred. |
| F-002 | High | API coverage gap | `docs/usecase/03-talent-lifecycle-uc.md`, `docs/api/openapi/03-talent-lifecycle.openapi.yaml` | Phase 3 use cases include interviews, offers, offboarding tasks, final clearance, training enrollments/results, asset returns; OpenAPI Phase 3 only exposes a small subset. | Add endpoints or annotate non-covered use cases as “not yet specified in API v1 draft”. |
| F-003 | High | API coverage gap | `docs/usecase/04-enterprise-extensions-uc.md`, `docs/api/openapi/04-enterprise-extensions.openapi.yaml` | Phase 4 use cases mention masking policies, mobile revoke, backup runs, archive operations, integration job lifecycle, but OpenAPI only partially covers them. | Add missing endpoints or mark Phase 4 API as skeletal draft. |
| F-004 | High | Auth design inconsistency | `docs/api/00-api-design.md`, `docs/api/openapi/*.yaml`, `docs/tech/00-technical-overview.md` | API design mentions both `BearerAuth` and `CookieAuth`, while OpenAPI files only declare `BearerAuth`, and tech design says API-only stateless. | Pick one primary contract for v1. Recommendation: keep BearerAuth only, move CookieAuth to note/future optional SSR adapter. |
| F-005 | Medium | Permission ownership drift | `docs/superpowers/specs/2026-06-30-phase1-ddd-domain-model.md`, `docs/erd/01-core-platform-erd.md`, `docs/api/openapi/01-core-platform.openapi.yaml` | DDD says `Permission` is a reference catalog owned by Configuration BC, but ERD/API treat role-permission mapping as part of Identity and do not define a clear permissions catalog table/endpoint. | Decide whether `permissions` belongs to Identity or Configuration, then align DDD + ERD + API. |
| F-006 | Medium | Auth/token model drift | `docs/api/00-api-design.md`, `docs/tech/00-technical-overview.md` | API design says token payload may contain cached `roles[]` and `data_scopes[]`, while tech design says permission/data scope resolved per request and not cached initially. | Standardize: either token carries only identity, or token carries cached authz claims. Recommend identity-only + server-side authz resolution first. |
| F-007 | Medium | Use case reference typo | `docs/usecase/00-use-case-map.md` | Relationship note says `UC-02.001` invokes `UC-02.011` (Send Notification), but `UC-02.011` is actually `Create Workflow Template`; `Send Notification` is `UC-02.012`. | Fix the use case reference IDs. |
| F-008 | Medium | API phase scope mismatch | `docs/api/00-api-design.md`, `docs/api/openapi/01-core-platform.openapi.yaml` | `00-api-design.md` says Phase 1 includes `/permissions`, `/data-scopes`, `/system-settings`, but OpenAPI file does not expose those paths. | Add endpoints or downgrade overview text from “includes” to “expected coverage”. |
| F-009 | Medium | DDD vs ERD table granularity | `docs/superpowers/specs/2026-06-30-phase1-ddd-domain-model.md`, `docs/erd/01-core-platform-erd.md` | DDD models `RoleBinding` and `DataScopeAssignment` as child/value concepts under `User`, but ERD stores them as separate tables (`user_roles`, `data_scope_assignments`). Not wrong, but should be explicitly called out as persistence decomposition. | Add one note in DDD or ERD clarifying aggregate-to-table decomposition. |
| F-010 | Medium | Company modeling under-specified | `docs/srs/00-enterprise-srs.md`, `docs/superpowers/specs/2026-06-30-phase1-ddd-domain-model.md`, `docs/erd/01-core-platform-erd.md` | SRS mentions company profile and company-level hierarchy, but DDD/ERD intentionally omit a `companies` table/aggregate in Phase 1. | Explicitly state “single enterprise installation → company stored as system-level singleton, not full aggregate/table in Phase 1” in ERD/API/Tech. |
| F-011 | Medium | Async job contract scope | `docs/api/00-api-design.md`, `docs/api/openapi/02-workforce-ops.openapi.yaml`, `docs/tech/02-workforce-ops-tech.md`, `docs/tech/04-enterprise-extensions-tech.md` | `jobs/{id}` polling pattern is defined only in Phase 2 OpenAPI, but async jobs also appear in Phase 4 (integration, analytics, archive). | Either declare `/jobs/{id}` as global shared endpoint in overview + Phase 4, or duplicate reference in Phase 4 spec. |
| F-012 | Low | Notification cardinality typo | `docs/erd/00-logical-erd.md` | `NOTIFICATION_MESSAGES ||--o{ USERS : sent_to` reverses likely ownership direction. In phase ERD it is `USERS ||--o{ NOTIFICATION_MESSAGES`. | Align logical ERD cardinality. |
| F-013 | Low | “JWT” wording too specific | `docs/api/openapi/*.yaml`, `docs/tech/*.md` | Security scheme uses `bearerFormat: JWT`, but tech stack says Sanctum. Sanctum bearer tokens are not necessarily JWT. | Change `bearerFormat` to generic `token` or note “opaque bearer token”. |
| F-014 | Low | Session wording drift | `docs/srs/01-core-platform-srs.md`, `docs/api/00-api-design.md`, `docs/tech/00-technical-overview.md` | SRS allows secure session or token-based auth, API/tech now bias strongly toward token. | Update SRS wording to say token-based primary, session optional adapter only. |
| F-015 | Low | Reporting/Analytics split is clear but under-explained | `docs/srs/02-workforce-ops-srs.md`, `docs/srs/04-enterprise-extensions-srs.md`, `docs/tech/02-workforce-ops-tech.md`, `docs/tech/04-enterprise-extensions-tech.md` | Reporting in Phase 2 and Analytics in Phase 4 are separate, but the docs do not explicitly define the boundary. | Add one line: Phase 2 = operational reports, Phase 4 = derived executive analytics and snapshots. |
| F-016 | Low | Backup evidence link in logical ERD is not repeated elsewhere | `docs/erd/00-logical-erd.md` | `BACKUP_RUNS ||--o{ AUDIT_LOGS : evidence` appears only in logical ERD, not in Phase 4 ERD or DDD. | Either remove from logical ERD or add matching note in Phase 4 ERD/DDD. |

## 3. Per-Pack Assessment

### 3.1 SRS

Strengths:

- Best baseline pack.
- Phase boundaries, NFRs, actors, and acceptance criteria are coherent.

Issues:

- Slightly more permissive auth wording than later API/Tech docs.
- Company model is implied more strongly than later technical docs implement.

### 3.2 DDD

Strengths:

- Aggregate boundaries are sensible and practical.
- Separation of Employee / Contract / EmployeeDocument is strong.

Issues:

- Permission catalog ownership needs one final decision.
- Some persistence decomposition assumptions are implicit rather than explicit.

### 3.3 ERD

Strengths:

- Good table decomposition.
- Good phase separation.

Issues:

- Minor logical ERD relationship typo.
- Some singleton/system tables (company, jobs) are implied elsewhere but not materialized consistently.

### 3.4 Use Case

Strengths:

- Good breadth and good fully-dressed coverage of critical flows.
- Best bridge between business and API.

Issues:

- One wrong reference (`UC-02.011` vs `UC-02.012`).
- API coverage lags behind use case inventory in later phases.

### 3.5 API

Strengths:

- OpenAPI 3.1, valid YAML, good baseline conventions.
- Resource + command pattern fits the use cases.

Issues:

- Under-specifies many non-core endpoints in Phases 2-4.
- Mixed token/session wording.
- JWT wording too specific for Sanctum-based architecture.

### 3.6 Technical Design

Strengths:

- Consistent architecture direction.
- Good queue/event/module decomposition.

Issues:

- Needs tighter alignment with the final chosen auth contract.
- Needs explicit boundary note for Reporting vs Analytics.

## 4. Recommended Fix Order

1. Fix auth contract drift (`F-004`, `F-006`, `F-013`, `F-014`).
2. Fix use case cross-reference typo (`F-007`).
3. Expand or explicitly scope-limit OpenAPI packs (`F-001`, `F-002`, `F-003`, `F-008`, `F-011`).
4. Resolve permission ownership and company singleton wording (`F-005`, `F-010`).
5. Clean low-severity ERD/reporting details (`F-012`, `F-015`, `F-016`).

## 5. Recommendation

No fundamental redesign is needed.

The document set is **mostly coherent**. The biggest work left is:

- tighten the auth model
- tighten API coverage vs use cases
- make a few ownership/boundary decisions explicit

After those fixes, the pack becomes much stronger as a source-of-truth for implementation.
