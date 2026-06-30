# Phase 4 Technical Design — Enterprise Extensions

Version: 0.1  
Date: 2026-06-30  
Status: Draft for review

## 1. Scope

Covers Enterprise Identity, Integration Hub, Mobile Gateway, Analytics, Compliance, and Operations.

## 2. Modules

```text
app/Modules/
  EnterpriseIdentity/  # SSO providers, federated identities, MFA, session control
  Integration/         # Endpoints, credentials, jobs, webhooks
  Mobile/              # Devices, sessions, push subscriptions
  Analytics/           # Definitions, snapshots, report runs
  Compliance/          # Retention, masking, evidence, export requests
  Operations/          # Job monitors, archives, backups, DR drills
```

## 3. Enterprise Identity

- SSO adapter boundary; Laravel `Auth` provider pattern
- `IdentityProvider` config stored in DB
- Secrets stored via env/secret manager, not domain entity
- `FederatedIdentity` maps external subject to local user
- Local authorization (roles + data scope) still applies
- MFA policy per scope/role
- Session revocation: write `revoked_at`; middleware checks

## 4. Integration Hub

- `IntegrationEndpoint` describes system type, direction, transport
- `IntegrationCredential` stores metadata only; secret material referenced
- `IntegrationJob` runs async via queue
- Retries: bounded, exponential backoff, dead-letter on max retries
- Webhook delivery: `WebhookSubscription` per event type
- Integration failures do not corrupt source domain transaction

## 5. Mobile Gateway

- Reuses existing API auth and permission/data-scope rules
- `MobileDevice` and `MobileSession` track client state
- Push subscription stored per device
- Revoke device invalidates all sessions and push

## 6. Analytics

- Read-model: `AnalyticsSnapshot` materializes metrics
- Snapshot generation via scheduled job
- Dashboard queries against snapshot tables
- Drill-down respects source data scope

## 7. Compliance

- `RetentionPolicy` defines lifecycle rules per data class
- `MaskingPolicy` applied at API response layer
- `DataExportRequest` gated by policy and approval
- `AuditEvidencePackage` bundles audit logs + records
- All sensitive export/download audited

## 8. Operations

- `BackgroundJobMonitor` tracks long-running jobs
- `ArchiveBatch` per (target_table, period_key)
- `BackupRun` records backup evidence
- `DisasterRecoveryDrill` records DR test results
- Archive jobs must respect legal hold

## 9. Performance Targets

- SSO login: <1s p95 (excluding external IdP latency)
- Analytics dashboard: <1s p95
- Compliance export request accepted: <500ms p95
- Archive/backup jobs: monitored, async

## 10. Security

- SSO subject map immutable per IdP
- Integration credentials encrypted at rest
- Mobile sessions scoped to user
- Analytics outputs respect data scope
- Compliance export download links time-limited
- Operations endpoints restricted to admin/ops roles

## 11. Test Strategy

- Identity provider mapper tests with fake assertion
- Integration job retry/backoff tests
- Masking policy unit tests
- Export request approval flow tests
- Archive reversibility/verification tests


Boundary note: Phase 4 Analytics is distinct from Phase 2 Reporting. Reporting remains operational and near-real-time; Analytics introduces derived snapshots, executive KPIs, and heavier asynchronous analytical runs.
