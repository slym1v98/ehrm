# Phase 2 Technical Design — Workforce Operations

Version: 0.1  
Date: 2026-06-30  
Status: Draft for review

## 1. Scope

Covers Attendance, Shift, Leave, Workflow, Notification, Payroll, and Reporting.

## 2. Modules

```text
app/Modules/
  Attendance/      # Raw logs, timesheets, adjustments, periods
  Shift/           # Templates and assignments
  Leave/           # Types, policies, requests, balances
  Workflow/        # Templates, requests, actions
  Notification/    # Templates, messages, preferences
  Payroll/         # Periods, runs, entries, payslips, adjustments
  Reporting/       # Definitions, runs
```

## 3. Queue Strategy

- Heavy work runs async via Redis queue
- Jobs:
  - `CalculateAttendanceTimesheetJob`
  - `RunPayrollJob`
  - `PublishPayslipsJob`
  - `GenerateReportJob`
  - `DeliverNotificationJob`
  - `SendWebhookJob`
- Job status tracked in DB; `GET /api/v1/jobs/{id}` returns status

## 4. Scheduler

- Attendance timesheet calculation: daily
- Attendance period close: monthly
- Payroll period generation: monthly
- Payslip publication: triggered by payroll lock
- Notification retries: exponential backoff

## 5. Database and Locking

- `attendance_timesheets` index on `(employee_id, work_date, shift_assignment_id)`
- `payroll_periods` unique `period_code`
- Redis-based locks for:
  - Payroll run per period
  - Attendance period close
  - Timesheet recalculation per employee/date
- Database advisory locks optional for critical sections

## 6. Workflow Engine

- Generic `WorkflowRequest` referenced by `subject_type` + `subject_id`
- Template-driven steps: approver strategy, escalation, on_reject
- Delegation via `SessionControl` style metadata
- Workflow state transitions persisted in `workflow_actions` history

## 7. Notification Fan-out

- Subscribers listen to domain/application events
- `NotificationMessage` row created; queued for delivery
- Channel-specific delivery adapter (email/SMS/Zalo/push)
- Delivery state tracked; failure logged but does not roll back business state

## 8. Payroll Architecture

- `PayrollFormulaEngine` is a domain service with versioned formula
- `TaxCalculator` and `InsuranceCalculator` isolated as services
- `AttendanceBasisCalculator` aggregates attendance inputs
- `PayrollEntry` uses effective-dated contract and employment data
- `PayrollPeriod` status enforced as domain invariant; locked period immutable except via privileged correction
- Payslip stored as file object in MinIO

## 9. Reporting Architecture

- Read-side: queries against operational tables
- Async via jobs for large reports
- Saved definitions for reuse
- Export results to MinIO file object with authorized download endpoint

## 10. Performance Targets

- Attendance raw log ingest: high throughput, no blocking
- Timesheet calculation: bounded, retries allowed
- Payroll run: async, status visible within seconds
- Leave/payslip APIs: <500ms p95

## 11. Security

- Payroll endpoints restricted to payroll/HR roles
- Payslip access: self + authorized roles only
- View/download audited
- Masking policy applied to payroll summary views unless caller has unmask permission

## 12. Test Strategy

- Domain unit tests for leave balance math, payroll formula, attendance calculation
- Application tests with fake workflow engine and notification adapters
- Integration tests with real Redis queues
- Concurrency tests for payroll lock and attendance close


Boundary note: Phase 2 Reporting covers operational reports and exports directly over transactional HR data. Phase 4 Analytics covers derived executive metrics, snapshots, and heavier analytical read models.
