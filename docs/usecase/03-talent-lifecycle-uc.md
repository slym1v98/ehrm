# Phase 3 Use Cases — Talent Lifecycle

Version: 0.1  
Date: 2026-06-30  
Status: Draft for review

## 1. Scope

Covers Recruitment, Onboarding, Offboarding, Performance, Training, and Asset use cases.

## 2. Fully Dressed Use Cases

### UC-03.001 — Hire Candidate

**Goal**: Recruiter progresses a candidate through the pipeline and converts them into an employee with onboarding plan.

**Primary Actor**: Recruiter

**Supporting Actors**: Hiring Manager, HR Manager, System (Onboarding, Employee Master)

**Trigger**: Hiring decision made.

**Preconditions**:
- Requisition exists and is open.
- Candidate has passed interviews and received an offer.
- Offer status = `accepted`.
- HR Manager has `CreateEmployee` permission.

**Postconditions**:
- Candidate status = `hired`.
- Employee record created.
- Onboarding plan created.
- Asset assignments and account provisioning requested as onboarding tasks.
- Audit log created.

**Main Success Scenario**:
1. Recruiter opens requisition detail.
2. System displays candidate pipeline.
3. Recruiter selects candidate with accepted offer.
4. System prompts for hire decision.
5. Recruiter clicks "Hire Candidate."
6. System validates offer is accepted.
7. System checks candidate is not already an employee (no duplicate email/phone).
8. System creates `Employee` aggregate using candidate personal data and offer terms.
9. System sets candidate status = `hired` and links to new employee.
10. System creates `OnboardingPlan` based on department/position template.
11. System creates onboarding tasks for HR, manager, IT, employee.
12. System sends notifications to onboarding task owners.
13. System closes requisition if all headcount filled.
14. System creates audit log.
15. System returns employee + onboarding summary to recruiter.

**Extensions**:
- 6a. Offer not accepted → System rejects.
- 7a. Duplicate email/phone found → System prompts for merge or rejection.
- 10a. No template available → System creates plan with default tasks.
- 11a. Task creation fails → System rolls back employee creation (compensation via event/manual recovery).

**Business Rules**:
- Candidate must convert to an employee, not replace it.
- Employee record is the source of truth after conversion.

**Notes**:
- Linked SRS: `REC-FR-008`, `ONB-FR-002`, `EMP-FR-001`.
- Linked DDD: `Candidate`, `Offer`, `Employee`, `OnboardingPlan` aggregates.
- Linked ERD: `candidates`, `offers`, `employees`, `onboarding_plans`, `onboarding_tasks`.

---

### UC-03.002 — Offboard Employee

**Goal**: HR Manager initiates and completes an employee exit, including clearance and final status update.

**Primary Actor**: HR Manager

**Supporting Actors**: Department Manager, IT/Admin Support, Payroll, System (Offboarding, Asset, Employee Master)

**Trigger**: Resignation submitted or termination decision made.

**Preconditions**:
- Employee exists and is in active/probation/onboarding status.
- HR Manager has `ManageOffboarding` permission and correct data scope.

**Postconditions**:
- `OffboardingRequest` created and approved.
- Offboarding tasks created and tracked.
- Asset return tasks tracked.
- Final clearance recorded.
- Employee status updated to `resigned` (after final working date).
- Audit log created.

**Main Success Scenario**:
1. HR Manager opens employee profile.
2. HR Manager clicks "Initiate Offboarding."
3. System prompts for: reason, requested last working date, initiator (employee or company).
4. HR Manager fills and submits.
5. System creates `OffboardingRequest` in pending status.
6. System creates `WorkflowRequest` for approval (if required by policy).
7. Manager/HR approves request.
8. System updates request status to `approved` and sets `approved_last_working_date`.
9. System creates `OffboardingPlan` with task set from template.
10. System creates tasks for: HR (records), Manager (handover), IT (account closure), Admin Support (asset return), Payroll (final salary), Employee (exit interview).
11. System sends notifications to task owners.
12. Task owners complete their tasks over time.
13. Asset returns tracked via `AssetReturn` records.
14. On/after approved last working date, HR Manager triggers final clearance.
15. System validates all mandatory tasks complete or waived.
16. System records `FinalClearance`.
17. System requests employee status change to `resigned` (separate UC).
18. System archives employee after retention period.
19. System creates audit log.

**Extensions**:
- 7a. Approval rejected → Request cancelled.
- 15a. Mandatory task incomplete → System blocks final clearance.
- 17a. Status change fails → System logs error; HR resolves manually.

**Business Rules**:
- Final clearance requires all mandatory tasks complete or waived.
- Account disablement must follow approved last working date.
- Final payroll references must be visible before clearance.

**Notes**:
- Linked SRS: `OFF-FR-001` through `OFF-FR-007`.
- Linked DDD: `OffboardingRequest`, `OffboardingPlan`, `FinalClearance`, `Employee` aggregates.
- Linked ERD: `offboarding_requests`, `offboarding_plans`, `offboarding_tasks`, `final_clearances`, `asset_returns`.

---

### UC-03.003 — Complete Performance Review

**Goal**: Manager and employee finalize a performance review for a cycle.

**Primary Actor**: Manager (with Employee input)

**Supporting Actors**: HR Manager (cycle admin), System (Notification)

**Trigger**: Performance cycle reaches review stage.

**Preconditions**:
- Performance cycle is active and review window is open.
- Employee is part of the cycle population.
- Goals are defined.

**Postconditions**:
- Self-assessment submitted.
- Manager assessment submitted.
- Review finalized with score and conclusion.
- Audit log created.

**Main Success Scenario**:
1. HR Manager opens active performance cycle.
2. System displays review population.
3. Employee opens own review.
4. System displays goals and self-assessment form.
5. Employee fills self-assessment and submits.
6. System saves self-assessment portion in `PerformanceReview`.
7. Manager opens employee's review.
8. System displays self-assessment and manager-assessment form.
9. Manager fills assessment, comments, and score.
10. Manager submits review.
11. System validates weight totals and score policy.
12. System calculates final score (or stores manager score).
13. System sets review status to `finalized`.
14. System sends notification to employee.
15. System creates audit log.

**Extensions**:
- 11a. Weight total invalid → System rejects submission.
- 12a. Calculation fails → System marks review with error.
- 14a. Notification fails → System logs but does not roll back.

**Business Rules**:
- Finalized reviews are immutable except via privileged correction.
- Weight totals must satisfy scoring policy.
- Performance results inform compensation decisions but do not automatically change payroll in this phase.

**Notes**:
- Linked SRS: `PRF-FR-003`, `PRF-FR-004`, `PRF-FR-005`, `PRF-FR-007`.
- Linked DDD: `PerformanceCycle`, `PerformanceReview`, `Goal` aggregates.
- Linked ERD: `performance_cycles`, `performance_reviews`, `goals`.

---

## 3. Brief Use Cases

### UC-03.004 — Create Recruitment Requisition

**Primary Actor**: Manager

**Goal**: Request headcount for a position.

**Trigger**: New role needed.

**Outcome**: `RecruitmentRequisition` created; routed for approval; audit log created.

---

### UC-03.005 — Add Candidate

**Primary Actor**: Recruiter

**Goal**: Register candidate with CV and contact info.

**Trigger**: Application received.

**Outcome**: `Candidate` record created with `cv_file_object_id`; duplicate check performed.

---

### UC-03.006 — Schedule Interview

**Primary Actor**: Recruiter

**Goal**: Arrange interview with interviewers.

**Trigger**: Candidate ready for interview.

**Outcome**: `Interview` record created with scheduled time and interviewer list; notifications sent.

---

### UC-03.007 — Submit Interview Scorecard

**Primary Actor**: Interviewer

**Goal**: Evaluate candidate after interview.

**Trigger**: Interview completed.

**Outcome**: Scorecard saved on `Interview`; immutable after submission.

---

### UC-03.008 — Send Offer

**Primary Actor**: Recruiter

**Goal**: Issue offer letter to candidate.

**Trigger**: Candidate passes interview.

**Outcome**: `Offer` record created with terms; notification sent.

---

### UC-03.009 — Create Onboarding Plan

**Primary Actor**: HR Staff

**Goal**: Prepare onboarding tasks for new hire.

**Trigger**: New hire created or hire event.

**Outcome**: `OnboardingPlan` created with tasks; owners notified.

---

### UC-03.010 — Complete Onboarding Task

**Primary Actor**: Various task owners

**Goal**: Mark onboarding task as done with proof.

**Trigger**: Task work finished.

**Outcome**: `OnboardingTask` status updated; plan progress updated.

---

### UC-03.011 — Request Offboarding

**Primary Actor**: Employee / Manager

**Goal**: Initiate offboarding with reason and proposed leaving date.

**Trigger**: Resignation or termination decision.

**Outcome**: `OffboardingRequest` created; routed for approval.

---

### UC-03.012 — Complete Offboarding Task

**Primary Actor**: Various task owners

**Goal**: Mark exit task as done.

**Trigger**: Task work finished.

**Outcome**: `OffboardingTask` status updated.

---

### UC-03.013 — Issue Final Clearance

**Primary Actor**: HR Manager

**Goal**: Approve final clearance after all mandatory tasks complete.

**Trigger**: All mandatory tasks done or waived.

**Outcome**: `FinalClearance` recorded; employee eligible for status change to `resigned`.

---

### UC-03.014 — Start Performance Cycle

**Primary Actor**: HR Manager

**Goal**: Begin a new review period.

**Trigger**: Calendar or HR decision.

**Outcome**: `PerformanceCycle` created with population; reviews opened.

---

### UC-03.015 — Submit Self-Assessment

**Primary Actor**: Employee

**Goal**: Rate own performance against goals.

**Trigger**: Cycle in self-assessment stage.

**Outcome**: Self-assessment portion saved on `PerformanceReview`.

---

### UC-03.016 — Submit Manager Review

**Primary Actor**: Manager

**Goal**: Rate employee performance.

**Trigger**: Cycle in manager-review stage.

**Outcome**: Manager assessment saved; review moves to finalization.

---

### UC-03.017 — Schedule Training Session

**Primary Actor**: Trainer

**Goal**: Plan a training session for a course.

**Trigger**: Training need.

**Outcome**: `TrainingSession` created with capacity and schedule.

---

### UC-03.018 — Enroll in Training

**Primary Actor**: Employee / HR

**Goal**: Register for a training session.

**Trigger**: Training announcement or HR assignment.

**Outcome**: `TrainingEnrollment` created; capacity check enforced.

---

### UC-03.019 — Record Training Result

**Primary Actor**: Trainer

**Goal**: Capture completion and score.

**Trigger**: Training session ended.

**Outcome**: `TrainingResult` created with score/certificate.

---

### UC-03.020 — Assign Asset

**Primary Actor**: IT/Admin Support

**Goal**: Issue asset to employee.

**Trigger**: Onboarding or role change.

**Outcome**: `AssetAssignment` created; asset marked issued.

---

### UC-03.021 — Return Asset

**Primary Actor**: Employee / IT/Admin

**Goal**: Return asset and record condition.

**Trigger**: Offboarding or role change.

**Outcome**: `AssetReturn` created; asset status updated; condition logged.
