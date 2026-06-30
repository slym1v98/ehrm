# Phase 1 Use Cases — Core Platform

Version: 0.1  
Date: 2026-06-30  
Status: Draft for review

## 1. Scope

Covers Identity & Access, Organization, Employee Master, Contract, Document, Configuration, and Audit use cases.

## 2. Fully Dressed Use Cases

### UC-01.001 — Create Employee

**Goal**: HR creates a new employee profile with personal and employment data.

**Primary Actor**: HR Manager / HR Staff

**Supporting Actors**: System (CodeGenerationRule)

**Trigger**: New hire needs a system profile.

**Preconditions**:
- HR user has `CreateEmployee` permission and correct data scope.
- At least one active branch, department, and position exist.
- Employee code generation rule is configured.

**Postconditions**:
- Employee record exists with generated code.
- Initial `EmployeeHistory` record created with effective_date = hire_date.
- Audit log created.

**Main Success Scenario**:
1. HR opens employee create form.
2. System displays required fields: employee code (auto-generated), full name, DOB, gender, personal email, phone, address, branch, department, position, manager (optional), employment type, hire date, status.
3. HR fills required fields and submits.
4. System validates all required fields are present.
5. System validates branch, department, position references are active.
6. System validates manager reference is a valid active employee (if provided).
7. System generates employee code via `CodeGenerationRule` aggregate.
8. System saves `Employee` aggregate.
9. System creates initial `EmployeeHistory` record.
10. System creates audit log entry.
11. System returns employee profile to HR.

**Extensions**:
- 4a. Required field missing → System returns validation error.
- 5a. Branch/department/position inactive or not found → System rejects with error.
- 6a. Manager reference invalid → System rejects with error.
- 7a. Code generation fails (e.g., rule not configured) → System rejects with error.
- 8a. Employee code already exists → System rejects with error (retry generation or manual override).

**Business Rules**:
- Employee code must be unique.
- Initial status defaults to `draft`, `onboarding`, or `probation` based on employment type config.
- Manager must not create a reporting cycle.

**Notes**:
- Linked SRS: `EMP-FR-001`, `EMP-FR-002`, `EMP-FR-003`, `EMP-FR-004`, `EMP-FR-007`.
- Linked DDD: `Employee` aggregate, `EmployeeCodeGenerator` domain service, `EmployeeHistory` child entity.
- Linked ERD: `employees`, `employee_history`, `audit_logs`.

---

### UC-01.002 — Assign User Role with Data Scope

**Goal**: Admin grants a user a role and defines their data access scope.

**Primary Actor**: Admin

**Supporting Actors**: —

**Trigger**: User needs access to specific HR modules and data.

**Preconditions**:
- Admin has `ManageUserRoles` permission.
- User account exists.
- Role exists and is active.
- If data scope is branch/department, those entities exist.

**Postconditions**:
- User has new role binding.
- User has new data scope assignment.
- Audit log created.

**Main Success Scenario**:
1. Admin opens user role assignment screen.
2. System displays user list.
3. Admin selects target user.
4. System displays available roles.
5. Admin selects role.
6. System prompts for data scope: self, direct reports, department, branch, all company.
7. Admin selects scope type and (if needed) specific branch/department.
8. System validates role is active.
9. System validates scope references (branch/department) exist if applicable.
10. System checks for duplicate active role assignment.
11. System saves `UserRole` and `DataScopeAssignment` records.
12. System creates audit log.
13. System confirms assignment to admin.

**Extensions**:
- 8a. Role inactive → System rejects with error.
- 9a. Branch/department reference invalid → System rejects with error.
- 10a. User already has this role actively assigned → System rejects or prompts for replacement.

**Business Rules**:
- A user may have multiple roles.
- Data scope for a role is independent; a user may have different scopes for different roles.
- Revoking a role does not delete the record; it sets `revoked_at`.

**Notes**:
- Linked SRS: `IAM-FR-004`, `IAM-FR-006`, `IAM-FR-007`.
- Linked DDD: `User` aggregate, `Role` aggregate, `DataScopeAssignment` value object.
- Linked ERD: `user_roles`, `data_scope_assignments`, `audit_logs`.

---

### UC-01.003 — Renew Contract

**Goal**: HR renews an expiring employment contract by creating a successor contract.

**Primary Actor**: HR Manager / HR Staff

**Supporting Actors**: —

**Trigger**: Employee contract is nearing expiry.

**Preconditions**:
- HR has `ManageContracts` permission and correct data scope.
- Existing contract exists and is active or near expiry.
- Employee exists.

**Postconditions**:
- New contract (draft or active) created.
- Predecessor contract marked as `renewed` when new contract activates.
- Audit log created.

**Main Success Scenario**:
1. HR searches for employee or contract near expiry.
2. System displays expiring contract details.
3. HR selects "Renew Contract" action.
4. System creates a draft successor contract with copied terms (type, start date = old end date + 1 day, etc.).
5. HR reviews and optionally updates terms, end date.
6. HR submits renewed contract.
7. System validates new start_date > predecessor end_date or overlaps are policy-allowed.
8. System validates contract type rules (e.g., definite requires end_date).
9. System saves new `EmployeeContract` aggregate with `predecessor_contract_id` = old contract id.
10. System creates audit log.
11. System optionally activates new contract (depending on HR action).
12. When new contract becomes active, system marks predecessor contract status = `renewed`.

**Extensions**:
- 7a. Start date conflict or invalid overlap → System rejects with error.
- 8a. Contract type validation fails → System rejects with error.

**Business Rules**:
- Renewal does not overwrite the historical contract.
- `predecessor_contract_id` links successor to original.
- Only one active contract per employee per overlapping period unless policy explicitly allows.

**Notes**:
- Linked SRS: `CON-FR-006`, `CON-FR-007`.
- Linked DDD: `Contract` aggregate, `ContractRenewalPolicy` domain service.
- Linked ERD: `employee_contracts`.

---

### UC-01.004 — Upload Employee Document

**Goal**: HR uploads a document (ID, contract scan, certificate) with metadata and stores it securely.

**Primary Actor**: HR Manager / HR Staff

**Supporting Actors**: System (MinIO, file storage)

**Trigger**: HR needs to attach an employee document.

**Preconditions**:
- HR has `UploadDocument` permission and correct data scope.
- Employee exists.
- Document type is configured in system.
- MinIO is available.

**Postconditions**:
- File uploaded to MinIO private bucket.
- `EmployeeDocument` metadata created.
- Audit log created.

**Main Success Scenario**:
1. HR opens employee document upload screen.
2. HR selects employee.
3. System displays employee's existing documents.
4. HR clicks "Upload Document."
5. System prompts for: document type, category, issue date, expiry date (if applicable), file.
6. HR selects file and fills metadata.
7. System validates file type and size against policy.
8. System uploads file to MinIO private bucket.
9. System receives object key and checksum from MinIO.
10. System saves `EmployeeDocument` aggregate with `file_object_id`.
11. System creates audit log.
12. System confirms upload to HR.

**Extensions**:
- 7a. File type not allowed → System rejects with error.
- 7b. File size exceeds limit → System rejects with error.
- 8a. MinIO upload fails → System retries or rejects; does not commit metadata.
- 9a. Checksum mismatch → System rejects upload.

**Business Rules**:
- Documents are private by default; access requires permission + data scope.
- Document expiry date is required for document types configured with expiry.
- Physical file deletion requires privileged action and audit trail.

**Notes**:
- Linked SRS: `DOC-FR-001`, `DOC-FR-002`, `DOC-FR-004`, `DOC-FR-006`, `DOC-FR-007`.
- Linked DDD: `EmployeeDocument` aggregate, `FileObject` value object.
- Linked ERD: `employee_documents`, `file_objects`, `audit_logs`.

---

## 3. Brief Use Cases

### UC-01.005 — Update Employee Personal Info

**Primary Actor**: HR Staff

**Goal**: Change employee contact info or address.

**Trigger**: Employee reports updated contact details.

**Outcome**: Employee personal fields updated; audit log created.

---

### UC-01.006 — Transfer Employee Department

**Primary Actor**: HR Manager

**Goal**: Move employee to a different department.

**Trigger**: Organizational change or employee promotion.

**Outcome**: Employee current department changed; `EmployeeHistory` record created with effective date; audit log created.

---

### UC-01.007 — Change Employee Manager

**Primary Actor**: HR Manager

**Goal**: Update direct manager for an employee.

**Trigger**: Manager change due to restructure or transfer.

**Outcome**: `EmployeeReportingLine` updated or new record created; audit log created.

---

### UC-01.008 — Change Employee Status

**Primary Actor**: HR Manager

**Goal**: Transition employee lifecycle status (e.g., probation → active, active → resigned).

**Trigger**: Lifecycle milestone reached.

**Outcome**: Employee status updated; `EmployeeHistory` record created; audit log created.

---

### UC-01.009 — Link User to Employee

**Primary Actor**: Admin

**Goal**: Associate a user account with an employee profile.

**Trigger**: Employee needs login access.

**Outcome**: `users.employee_id` set; audit log created.

---

### UC-01.010 — Create Branch

**Primary Actor**: Admin

**Goal**: Define a new branch/office.

**Trigger**: Company opens a new location.

**Outcome**: Branch record created; audit log created.

---

### UC-01.011 — Create Department

**Primary Actor**: Admin

**Goal**: Define a new department.

**Trigger**: Organizational expansion.

**Outcome**: Department record created with optional parent; cycle check enforced; audit log created.

---

### UC-01.012 — Create Position

**Primary Actor**: Admin

**Goal**: Define a new job title/grade.

**Trigger**: New role needed in org structure.

**Outcome**: Position record created; audit log created.

---

### UC-01.013 — View Employee Profile

**Primary Actor**: Employee

**Goal**: See own profile data.

**Trigger**: Employee wants to review personal info.

**Outcome**: Employee sees allowed fields; sensitive fields (salary, tax) may be masked per policy.

---

### UC-01.014 — Deactivate User Account

**Primary Actor**: Admin

**Goal**: Disable login for a user.

**Trigger**: User no longer needs access or security reason.

**Outcome**: User status = disabled; login blocked; audit log created.

---

### UC-01.015 — Configure Lookup Values

**Primary Actor**: Admin

**Goal**: Add or deactivate lookup values (e.g., employment type, document type).

**Trigger**: Business rule change.

**Outcome**: Lookup value created or deactivated; audit log created.

---

### UC-01.016 — View Audit Logs

**Primary Actor**: Admin / HR Manager

**Goal**: Search action history for compliance or troubleshooting.

**Trigger**: Security review or user issue investigation.

**Outcome**: Audit log search results displayed within user data scope.
