# Phase 2 Use Cases — Workforce Operations

Version: 0.1  
Date: 2026-06-30  
Status: Draft for review

## 1. Scope

Covers Attendance, Shift, Leave, Workflow, Notification, Payroll, and Reporting use cases.

## 2. Fully Dressed Use Cases

### UC-02.001 — Apply for Leave

**Goal**: Employee requests time off via self-service portal.

**Primary Actor**: Employee

**Supporting Actors**: System (Workflow engine, Notification)

**Trigger**: Employee needs time off.

**Preconditions**:
- Employee has `CreateLeaveRequest` permission (self-scoped).
- Leave type is active.
- Required lookup/policy values exist (working calendar, leave policy).

**Postconditions**:
- `LeaveRequest` created in pending status.
- `WorkflowRequest` started.
- Notification sent to approver.
- Audit log created.

**Main Success Scenario**:
1. Employee opens leave request screen.
2. System displays leave type options and available balance.
3. Employee selects leave type.
4. System displays start date, end date, duration unit (day, half, hour), reason fields.
5. Employee selects date range and duration.
6. System previews requested duration and remaining balance.
7. System validates against policy (overlap, balance, request window, public holidays).
8. Employee submits request.
9. System creates `LeaveRequest` in pending status.
10. System creates `WorkflowRequest` linked to leave request.
11. System sends notification to first approver.
12. System returns submission confirmation to employee.

**Extensions**:
- 7a. Overlap with existing approved/pending leave → System rejects with error.
- 7b. Insufficient balance (if balance-tracked type) → System rejects or warns based on policy.
- 7c. Request window violation (e.g., too early/late) → System rejects or warns.
- 8a. Policy validation fails → System rejects submission.

**Business Rules**:
- Balance deduction occurs only on approval (or configured approval stage).
- Approved leave supersedes absence classification.
- Pending leave blocks new overlapping requests for the same employee.

**Notes**:
- Linked SRS: `LEA-FR-003`, `LEA-FR-004`, `LEA-FR-005`, `LEA-FR-007`.
- Linked DDD: `LeaveRequest` aggregate, `WorkflowRequest` aggregate.
- Linked ERD: `leave_requests`, `leave_balances`, `workflow_requests`, `notification_messages`.

---

### UC-02.002 — Approve Leave Request

**Goal**: Manager approves or rejects a pending leave request.

**Primary Actor**: Department Manager / Approver

**Supporting Actors**: System (Notification)

**Trigger**: Pending leave request routed to approver.

**Preconditions**:
- Approver has `ApproveLeaveRequest` permission and correct data scope.
- Leave request is in pending status.
- Approver is the current step of the workflow.

**Postconditions**:
- `LeaveRequest` status updated to approved or rejected.
- `WorkflowRequest` advances or completes.
- Notification sent to employee and next approver (if any).
- Audit log created.

**Main Success Scenario**:
1. Manager opens approval inbox.
2. System displays pending approval requests.
3. Manager selects leave request.
4. System displays request details: employee, leave type, dates, duration, reason, balance impact, employee schedule context.
5. Manager selects "Approve" or "Reject."
6. If reject, Manager provides required comment.
7. Manager submits decision.
8. System validates decision is allowed (right approver, request still pending).
9. System updates `LeaveRequest` status to approved or rejected.
10. If approved, System updates `LeaveBalance` (deducts used/accrued).
11. If approved, System triggers attendance recalculation for affected dates.
12. System advances `WorkflowRequest` to next step or completes.
13. System sends notification to employee and next approver (if any).
14. System creates audit log.
15. System confirms decision to manager.

**Extensions**:
- 8a. Manager no longer authorized (e.g., role changed) → System rejects with error.
- 8b. Request already approved/cancelled → System rejects.
- 13a. Notification delivery fails → System logs failure but does not roll back approval.

**Business Rules**:
- Approval status changes are immutable except via privileged reversal.
- Delegated approvals preserve original approver in audit history.

**Notes**:
- Linked SRS: `LEA-FR-005`, `WFL-FR-004`, `NTF-FR-004`.
- Linked DDD: `LeaveRequest`, `WorkflowRequest`, `LeaveBalance` aggregates.
- Linked ERD: `leave_requests`, `workflow_requests`, `workflow_actions`, `leave_balances`.

---

### UC-02.003 — Capture Attendance and Request Adjustment

**Goal**: Employee records time and submits correction request when needed.

**Primary Actor**: Employee

**Supporting Actors**: System (Calculation engine, Workflow)

**Trigger**: Employee needs to record attendance or fix an error.

**Preconditions**:
- Employee is active and has check-in/out permission.
- For adjustment: timesheet exists and attendance period is not closed (or override allowed).

**Postconditions**:
- Raw attendance log created.
- Timesheet recalculated (after approval).
- Audit log created.

**Main Success Scenario**:
1. Employee opens attendance screen.
2. System displays today's attendance status and check-in button.
3. Employee clicks "Check In."
4. System records `AttendanceRawLog` with source = web, event_type = check_in, event_time = now.
5. System returns confirmation.
6. Later, Employee views daily attendance detail.
7. Employee notices missed check-out and clicks "Request Adjustment."
8. System displays adjustment form: target date, requested time, reason, evidence (file).
9. Employee fills form and submits.
10. System validates request window and duplicate submission rules.
11. System creates `AttendanceAdjustmentRequest` in pending status.
12. System starts workflow.
13. System sends notification to approver.
14. On approval (separate flow), System recalculates `AttendanceTimesheet` via calculation engine.
15. System creates audit log.

**Extensions**:
- 10a. Adjustment window expired → System rejects.
- 10b. Duplicate pending request for same date → System rejects.
- 14a. Recalculation fails → System logs error; approval state preserved for manual investigation.

**Business Rules**:
- Adjustments trigger recalculation, not direct overwrite.
- Approved adjustments are auditable.

**Notes**:
- Linked SRS: `ATT-FR-001`, `ATT-FR-006`, `ATT-FR-007`.
- Linked DDD: `AttendanceRawLog`, `AttendanceTimesheet`, `AttendanceAdjustmentRequest` aggregates.
- Linked ERD: `attendance_raw_logs`, `attendance_timesheets`, `attendance_adjustment_requests`.

---

### UC-02.004 — Run Monthly Payroll

**Goal**: Payroll officer calculates, reviews, approves, and locks monthly payroll.

**Primary Actor**: Payroll / Accountant

**Supporting Actors**: HR Manager (approver), System (calculation engine, notification)

**Trigger**: Monthly payroll cycle begins.

**Preconditions**:
- Target payroll period exists and is open.
- Attendance period is closed for the same month.
- Required payroll components are configured.
- Formula engine is configured.

**Postconditions**:
- Payroll run completed.
- Payroll entries calculated and reviewed.
- Payroll approved and locked (if no errors).
- Payslips published.
- Audit log created.

**Main Success Scenario**:
1. Payroll officer opens payroll period list.
2. System displays open period (e.g., 2026-06).
3. Payroll officer clicks "Start Payroll Run."
4. System validates attendance period is closed and required inputs are ready.
5. System creates `PayrollRun` in `running` status.
6. System enqueues calculation job.
7. Calculation engine processes each employee's `PayrollEntry` using effective-dated contract data, attendance data, leave data, components, and formula.
8. System updates `PayrollRun` to `completed`.
9. Payroll officer reviews calculated entries.
10. Payroll officer creates adjustments if needed (within `reviewing` window).
11. Payroll officer submits payroll for approval.
12. HR Manager / Approver reviews and approves payroll.
13. System updates payroll period status to `approved`.
14. Payroll officer locks payroll.
15. System updates payroll period status to `locked` (immutable).
16. System publishes payslips for each entry.
17. System sends notifications to employees about payslip availability.
18. System creates audit log.

**Extensions**:
- 4a. Attendance period not closed → System rejects with error.
- 7a. Calculation fails for one employee → System marks entry with error and continues; payroll officer reviews.
- 11a. Adjustment outside reviewing window → System rejects.
- 12a. Approver rejects → Payroll returns to `calculated`/`reviewing` status.
- 15a. Lock fails due to unfinished entries → System rejects lock.

**Business Rules**:
- Locked payroll periods are immutable except via privileged correction.
- `formula_version` is recorded on each run.
- Payslip access is restricted to self and authorized payroll/HR roles.

**Notes**:
- Linked SRS: `PAY-FR-001` through `PAY-FR-008`.
- Linked DDD: `PayrollPeriod`, `PayrollRun`, `PayrollEntry`, `Payslip`, `PayrollAdjustment` aggregates.
- Linked ERD: `payroll_periods`, `payroll_runs`, `payroll_entries`, `payslips`, `payroll_adjustments`, `notification_messages`.

---

## 3. Brief Use Cases

### UC-02.005 — Create Shift Template

**Primary Actor**: HR Manager

**Goal**: Define shift rules (start, end, tolerance, overtime).

**Trigger**: Need for a new shift type.

**Outcome**: `ShiftTemplate` created; audit log created.

---

### UC-02.006 — Assign Shift

**Primary Actor**: HR Staff

**Goal**: Assign shift to employee or department for a date range.

**Trigger**: Schedule planning.

**Outcome**: `ShiftAssignment` created; conflicting assignments blocked.

---

### UC-02.007 — Close Attendance Period

**Primary Actor**: HR Manager

**Goal**: Lock monthly attendance for payroll.

**Trigger**: End of month.

**Outcome**: Attendance period status = `closed`; subsequent edits require privileged reopen.

---

### UC-02.008 — Calculate Attendance Timesheet

**Primary Actor**: System

**Goal**: Derive daily timesheet from raw logs and shift context.

**Trigger**: New raw log or adjustment approved.

**Outcome**: `AttendanceTimesheet` recalculated with new values.

---

### UC-02.009 — Cancel Leave Request

**Primary Actor**: Employee

**Goal**: Withdraw pending leave request.

**Trigger**: Change of plans.

**Outcome**: `LeaveRequest` status = `cancelled`; workflow terminated; balance unaffected.

---

### UC-02.010 — View Leave Balance

**Primary Actor**: Employee

**Goal**: Check remaining leave per type.

**Trigger**: Self-service check.

**Outcome**: System displays `LeaveBalance` for current year and leave types.

---

### UC-02.011 — Create Workflow Template

**Primary Actor**: Admin

**Goal**: Configure approval flow (steps, approvers, conditions).

**Trigger**: New approval scenario.

**Outcome**: `WorkflowTemplate` created; audit log created.

---

### UC-02.012 — Send Notification

**Primary Actor**: System

**Goal**: Deliver message to recipient via configured channel.

**Trigger**: Domain/application event.

**Outcome**: `NotificationMessage` queued; delivery state tracked.

---

### UC-02.013 — Review Payroll Entry

**Primary Actor**: Payroll

**Goal**: Verify calculated payroll entries before approval.

**Trigger**: Payroll run completed.

**Outcome**: Payroll officer identifies and corrects anomalies.

---

### UC-02.014 — Approve Payroll

**Primary Actor**: Payroll / HR Manager

**Goal**: Approve payroll period before lock.

**Trigger**: Review complete.

**Outcome**: Payroll period status = `approved`.

---

### UC-02.015 — Publish Payslips

**Primary Actor**: Payroll

**Goal**: Make payslips available to employees.

**Trigger**: Payroll locked.

**Outcome**: `Payslip` created with file reference; notification sent to employees.

---

### UC-02.016 — View Payslip

**Primary Actor**: Employee

**Goal**: See own salary detail for a period.

**Trigger**: Self-service check.

**Outcome**: Payslip displayed; download audited.

---

### UC-02.017 — Generate Report

**Primary Actor**: HR Manager

**Goal**: Export workforce data (attendance, leave, payroll summary).

**Trigger**: Reporting need.

**Outcome**: Report data displayed or exported; export audited.

---

### UC-02.018 — Adjust Payroll Entry

**Primary Actor**: Payroll

**Goal**: Correct payroll entry before approval.

**Trigger**: Anomaly detected in review.

**Outcome**: `PayrollAdjustment` recorded; entry recomputed; audit log created.

---

### UC-02.019 — Configure Leave Policy

**Primary Actor**: Admin

**Goal**: Define leave type rules (accrual, carry-forward, expiry).

**Trigger**: Policy change.

**Outcome**: `LeavePolicy` created or updated; audit log created.
