# Phase 4 Use Cases — Enterprise Extensions

Version: 0.1  
Date: 2026-06-30  
Status: Draft for review

## 1. Scope

Covers SSO, integrations, mobile readiness, analytics, compliance, and operations use cases.

## 2. Fully Dressed Use Cases

### UC-04.001 — Enable SSO and Federate User

**Goal**: Admin connects an enterprise identity provider and maps external identities to local users.

**Primary Actor**: Admin

**Supporting Actors**: External Identity Provider, System

**Trigger**: Company wants centralized login through SSO.

**Preconditions**:
- Admin has enterprise identity management permission.
- Identity provider metadata is available.
- Local users exist or can be provisioned/mapped.

**Postconditions**:
- Identity provider is configured and active.
- External subject mapped to local user.
- SSO login succeeds for mapped user.
- Audit log created.

**Main Success Scenario**:
1. Admin opens Enterprise Identity settings.
2. Admin selects provider type (OIDC/SAML-like provider category).
3. Admin enters issuer/client metadata and mapping rules.
4. System validates provider metadata.
5. Admin activates provider.
6. System stores identity provider config (secret refs only; not raw secrets).
7. User attempts login via SSO.
8. System redirects user to external provider.
9. External provider authenticates user and returns assertion/token.
10. System validates assertion/token.
11. System resolves external subject to `FederatedIdentity` mapping.
12. System creates session for linked local user.
13. System applies local role/data-scope authorization.
14. System records federated login audit log.
15. User enters system.

**Extensions**:
- 4a. Provider metadata invalid → System rejects activation.
- 10a. Assertion/token validation fails → System rejects login and records failed audit.
- 11a. No mapping found → System rejects login or routes to admin-approved provisioning based on policy.
- 13a. Local user disabled → System rejects login.

**Business Rules**:
- SSO authenticates identity; local system still authorizes via roles and data scopes.
- Secrets are stored in secrets manager, not business table.
- Disabled local users cannot login even if SSO succeeds.

**Notes**:
- Linked SRS: `IAMX-FR-001`, `IAMX-FR-002`, `IAMX-FR-005`.
- Linked DDD: `IdentityProvider`, `FederatedIdentity`, `SessionControl`.
- Linked ERD: `identity_providers`, `federated_identities`, `session_controls`, `audit_logs`.

---

### UC-04.002 — Request Sensitive Data Export

**Goal**: Authorized user requests export of sensitive HR/payroll data under compliance controls.

**Primary Actor**: HR Manager / Compliance User

**Supporting Actors**: Admin (optional approver), System (Audit, Compliance)

**Trigger**: Legal, audit, payroll, or compliance need for sensitive data export.

**Preconditions**:
- User has permission to request sensitive export.
- Target data scope is within user's allowed data scope.
- Export policy exists.

**Postconditions**:
- `DataExportRequest` created.
- Export approved/rejected according to policy.
- If approved, file generated and stored privately.
- Export/download audit logs created.

**Main Success Scenario**:
1. User opens data export request screen.
2. System displays export categories (employee PII, payroll summary, audit evidence, etc.).
3. User selects category, filters, reason, and target date range.
4. System calculates estimated data scope and sensitivity level.
5. System validates user's permission and data scope.
6. User submits request.
7. System creates `DataExportRequest` in pending status.
8. System routes request for approval if policy requires.
9. Approver reviews reason and scope.
10. Approver approves export.
11. System generates export asynchronously.
12. System stores file in private object storage and links file object to export request.
13. System notifies requester.
14. Requester downloads file through authorized endpoint.
15. System records export generation and download audit logs.

**Extensions**:
- 5a. Requested scope exceeds user's data scope → System rejects.
- 8a. No approver available → System keeps request pending and alerts admin.
- 10a. Approver rejects → Request status = rejected; no file generated.
- 11a. Export job fails → Request status = failed; requester notified.
- 14a. Download link expired → User requests new authorized link.

**Business Rules**:
- Sensitive export must always be audited.
- Exported files are private, time-limited, and access-controlled.
- Masking policy applies unless user has explicit unmasked-export permission.
- Retention policy controls generated export lifetime.

**Notes**:
- Linked SRS: `CMP-FR-002`, `CMP-FR-003`, `CMP-FR-004`.
- Linked DDD: `DataExportRequest`, `MaskingPolicy`, `AuditEvidencePackage`.
- Linked ERD: `data_export_requests`, `masking_policies`, `audit_logs`, `file_objects`.

---

## 3. Brief Use Cases

### UC-04.003 — Register Integration Endpoint

**Primary Actor**: Admin

**Goal**: Configure external integration endpoint.

**Trigger**: Need to connect accounting, bank, device, or ERP system.

**Outcome**: `IntegrationEndpoint` and credential metadata created; secrets stored by reference; audit log created.

---

### UC-04.004 — Run Integration Job

**Primary Actor**: System

**Goal**: Execute data sync/export/import with external system.

**Trigger**: Schedule, webhook, or manual request.

**Outcome**: `IntegrationJob` completed/failed; retries bounded; audit/integration logs created.

---

### UC-04.005 — Register Mobile Device

**Primary Actor**: Employee

**Goal**: Enable mobile self-service access on a device.

**Trigger**: User logs in from mobile app.

**Outcome**: `MobileDevice` and `MobileSession` created; push subscription optional.

---

### UC-04.006 — Revoke Mobile Device

**Primary Actor**: Admin / Employee

**Goal**: Disable a lost/stolen device.

**Trigger**: Device reported lost or session risk detected.

**Outcome**: Device status revoked; sessions invalidated; push disabled.

---

### UC-04.007 — Generate Analytics Snapshot

**Primary Actor**: System

**Goal**: Materialize workforce metrics for period.

**Trigger**: Schedule or report demand.

**Outcome**: `AnalyticsSnapshot` generated with source period and timestamp.

---

### UC-04.008 — Generate Executive Report

**Primary Actor**: HR Manager / Executive

**Goal**: View high-level workforce KPI report.

**Trigger**: Management review.

**Outcome**: Dashboard/report generated; drill-down limited by permissions.

---

### UC-04.009 — Create Retention Policy

**Primary Actor**: Compliance

**Goal**: Define retention and archival rules for data classes.

**Trigger**: Compliance policy update.

**Outcome**: `RetentionPolicy` created; future archive jobs use policy.

---

### UC-04.010 — Apply Masking Policy

**Primary Actor**: Compliance

**Goal**: Mask sensitive fields by role/scope/report type.

**Trigger**: Privacy policy enforcement.

**Outcome**: `MaskingPolicy` applied to UI/API/export views.

---

### UC-04.011 — Run Archive Batch

**Primary Actor**: Operations

**Goal**: Archive old records under retention rules.

**Trigger**: Scheduled archival window.

**Outcome**: `ArchiveBatch` completed; evidence stored; no legal-hold data deleted.

---

### UC-04.012 — Record Backup Run

**Primary Actor**: Operations

**Goal**: Record backup execution and evidence.

**Trigger**: Scheduled backup completed.

**Outcome**: `BackupRun` stored with status, timestamp, evidence.
