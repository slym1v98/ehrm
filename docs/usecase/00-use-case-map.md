# Use Case Map — iHRM

Version: 0.1  
Date: 2026-06-30  
Status: Draft for review

## 1. Purpose

This document provides a cross-phase use case inventory for the iHRM system. Detailed use cases live in phase-specific files:

- `01-core-platform-uc.md`
- `02-workforce-ops-uc.md`
- `03-talent-lifecycle-uc.md`
- `04-enterprise-extensions-uc.md`

## 2. Actor Map

| Actor | Phase | Description |
| --- | --- | --- |
| Admin | 1-4 | System administrator; manages roles, permissions, master data, config. |
| HR Manager | 1-3 | Owns HR operations; employee master, contracts, payroll oversight, recruitment, performance. |
| HR Staff | 1-3 | Maintains employee records, assists recruitment/onboarding/offboarding. |
| Department Manager | 1-3 | Reviews employees in department; approves leave, attendance adjustments, performance reviews. |
| Employee | 1-4 | Self-service profile, attendance, leave, payslip, training, approvals. |
| Accountant / Payroll | 2 | Processes payroll calculations, approvals, adjustments, exports. |
| Recruiter | 3 | Manages job openings, candidate pipeline, interviews, offers. |
| Interviewer | 3 | Evaluates candidates and submits scorecards. |
| Trainer | 3 | Manages training courses, sessions, enrollments, results. |
| IT/Admin Support | 3 | Prepares/recovers accounts, assets during onboarding/offboarding. |

## 3. Use Case Inventory

Format:

- **ID**: `UC-{phase}.{seq}`
- **Name**: Short goal-oriented name
- **Primary Actor**: Who initiates
- **Goal**: Business outcome
- **Type**: Fully Dressed (FD) or Brief (B)

### Phase 1 — Core Platform

| ID | Name | Primary Actor | Goal | Type |
| --- | --- | --- | --- | --- |
| UC-01.001 | Create Employee | HR Manager/Staff | Add new employee profile | FD |
| UC-01.002 | Assign User Role with Data Scope | Admin | Grant user role and data access | FD |
| UC-01.003 | Renew Contract | HR Manager/Staff | Create successor contract | FD |
| UC-01.004 | Upload Employee Document | HR Manager/Staff | Attach document with metadata | FD |
| UC-01.005 | Update Employee Personal Info | HR Staff | Change contact/address data | B |
| UC-01.006 | Transfer Employee Department | HR Manager | Change department assignment | B |
| UC-01.007 | Change Employee Manager | HR Manager | Update reporting line | B |
| UC-01.008 | Change Employee Status | HR Manager | Update lifecycle status | B |
| UC-01.009 | Link User to Employee | Admin | Associate login with employee | B |
| UC-01.010 | Create Branch | Admin | Define branch/office | B |
| UC-01.011 | Create Department | Admin | Define department | B |
| UC-01.012 | Create Position | Admin | Define job title/grade | B |
| UC-01.013 | View Employee Profile | Employee | See own profile | B |
| UC-01.014 | Deactivate User Account | Admin | Disable login | B |
| UC-01.015 | Configure Lookup Values | Admin | Manage config catalogs | B |
| UC-01.016 | View Audit Logs | Admin/HR Manager | Search action history | B |

### Phase 2 — Workforce Operations

| ID | Name | Primary Actor | Goal | Type |
| --- | --- | --- | --- | --- |
| UC-02.001 | Apply for Leave | Employee | Request time off | FD |
| UC-02.002 | Approve Leave Request | Manager | Approve/reject leave | FD |
| UC-02.003 | Capture Attendance and Request Adjustment | Employee | Record time + fix errors | FD |
| UC-02.004 | Run Monthly Payroll | Payroll | Calculate salary | FD |
| UC-02.005 | Create Shift Template | HR Manager | Define shift rules | B |
| UC-02.006 | Assign Shift | HR Staff | Assign shift to employee/dept | B |
| UC-02.007 | Close Attendance Period | HR Manager | Lock attendance data | B |
| UC-02.008 | Calculate Attendance Timesheet | System | Derive daily result | B |
| UC-02.009 | Cancel Leave Request | Employee | Withdraw pending leave | B |
| UC-02.010 | View Leave Balance | Employee | Check remaining leave | B |
| UC-02.011 | Create Workflow Template | Admin | Configure approval flow | B |
| UC-02.012 | Send Notification | System | Deliver message | B |
| UC-02.013 | Review Payroll Entry | Payroll | Check payroll before approve | B |
| UC-02.014 | Approve Payroll | Payroll/HR Manager | Lock payroll period | B |
| UC-02.015 | Publish Payslips | Payroll | Make payslips available | B |
| UC-02.016 | View Payslip | Employee | See salary detail | B |
| UC-02.017 | Generate Report | HR Manager | Export workforce data | B |
| UC-02.018 | Adjust Payroll Entry | Payroll | Correct before approval | B |
| UC-02.019 | Configure Leave Policy | Admin | Set leave rules | B |

### Phase 3 — Talent Lifecycle

| ID | Name | Primary Actor | Goal | Type |
| --- | --- | --- | --- | --- |
| UC-03.001 | Hire Candidate | Recruiter/HR | Move candidate to employee | FD |
| UC-03.002 | Offboard Employee | HR Manager | Exit employee | FD |
| UC-03.003 | Complete Performance Review | Manager/Employee | Finalize review cycle | FD |
| UC-03.004 | Create Recruitment Requisition | Manager | Request headcount | B |
| UC-03.005 | Add Candidate | Recruiter | Register candidate | B |
| UC-03.006 | Schedule Interview | Recruiter | Arrange interview | B |
| UC-03.007 | Submit Interview Scorecard | Interviewer | Evaluate candidate | B |
| UC-03.008 | Send Offer | Recruiter | Issue offer letter | B |
| UC-03.009 | Create Onboarding Plan | HR Staff | Prepare new hire tasks | B |
| UC-03.010 | Complete Onboarding Task | Various | Mark task done | B |
| UC-03.011 | Request Offboarding | Employee/Manager | Initiate exit | B |
| UC-03.012 | Complete Offboarding Task | Various | Clear exit item | B |
| UC-03.013 | Issue Final Clearance | HR Manager | Approve exit completion | B |
| UC-03.014 | Start Performance Cycle | HR Manager | Begin review period | B |
| UC-03.015 | Submit Self-Assessment | Employee | Rate own performance | B |
| UC-03.016 | Submit Manager Review | Manager | Rate employee | B |
| UC-03.017 | Schedule Training Session | Trainer | Plan course session | B |
| UC-03.018 | Enroll in Training | Employee | Register for training | B |
| UC-03.019 | Record Training Result | Trainer | Capture completion/score | B |
| UC-03.020 | Assign Asset | IT/Admin Support | Issue item to employee | B |
| UC-03.021 | Return Asset | Employee/IT | Return item | B |

### Phase 4 — Enterprise Extensions

| ID | Name | Primary Actor | Goal | Type |
| --- | --- | --- | --- | --- |
| UC-04.001 | Enable SSO and Federate User | Admin | Connect external IdP | FD |
| UC-04.002 | Request Sensitive Data Export | HR Manager/Compliance | Securely export PII/payroll | FD |
| UC-04.003 | Register Integration Endpoint | Admin | Configure external system | B |
| UC-04.004 | Run Integration Job | System | Execute sync/export | B |
| UC-04.005 | Register Mobile Device | Employee | Enable mobile access | B |
| UC-04.006 | Revoke Mobile Device | Admin | Disable lost/stolen device | B |
| UC-04.007 | Generate Analytics Snapshot | System | Materialize metrics | B |
| UC-04.008 | Generate Executive Report | HR Manager | View workforce KPIs | B |
| UC-04.009 | Create Retention Policy | Compliance | Define data lifecycle | B |
| UC-04.010 | Apply Masking Policy | Compliance | Mask sensitive fields | B |
| UC-04.011 | Run Archive Batch | Operations | Archive old records | B |
| UC-04.012 | Record Backup Run | Operations | Log backup evidence | B |

## 4. Traceability Matrix

| Use Case | SRS Requirement | DDD Aggregate | ERD Table |
| --- | --- | --- | --- |
| UC-01.001 | EMP-FR-001 | Employee | employees, employee_history |
| UC-01.002 | IAM-FR-004, IAM-FR-006 | User, Role, DataScopeAssignment | users, user_roles, data_scope_assignments |
| UC-01.003 | CON-FR-006 | Contract | employee_contracts |
| UC-01.004 | DOC-FR-001 | EmployeeDocument | employee_documents, file_objects |
| UC-02.001 | LEA-FR-004 | LeaveRequest | leave_requests, leave_balances |
| UC-02.002 | LEA-FR-005 | WorkflowRequest | workflow_requests, workflow_actions |
| UC-02.003 | ATT-FR-006 | AttendanceAdjustmentRequest | attendance_adjustment_requests |
| UC-02.004 | PAY-FR-002 | PayrollRun, PayrollEntry | payroll_runs, payroll_entries |
| UC-03.001 | REC-FR-008 | Candidate, Employee, OnboardingPlan | candidates, employees, onboarding_plans |
| UC-03.002 | OFF-FR-001 | OffboardingRequest | offboarding_requests, offboarding_plans |
| UC-03.003 | PRF-FR-005 | PerformanceReview | performance_reviews |
| UC-04.001 | IAMX-FR-001 | IdentityProvider, FederatedIdentity | identity_providers, federated_identities |
| UC-04.002 | CMP-FR-002 | DataExportRequest | data_export_requests |

## 5. Use Case Relationships

- `UC-02.001` (Apply for Leave) invokes `UC-02.012` (Send Notification) after submission.
- `UC-02.002` (Approve Leave) invokes `UC-02.008` (Calculate Attendance) to update timesheet.
- `UC-02.004` (Run Monthly Payroll) depends on `UC-02.007` (Close Attendance Period).
- `UC-03.001` (Hire Candidate) invokes `UC-01.001` (Create Employee) + `UC-03.009` (Create Onboarding Plan).
- `UC-03.002` (Offboard Employee) invokes `UC-03.021` (Return Asset) + `UC-01.008` (Change Employee Status).

## 6. Notes

- Fully dressed use cases (FD) contain: preconditions, main flow, extensions, postconditions, business rules.
- Brief use cases (B) contain: actor, goal, trigger, outcome only.
- All use cases are traceable to SRS, DDD aggregates, and ERD tables.
