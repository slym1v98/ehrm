# Phase 1 Employee Design

Version: 0.1
Date: 2026-07-01
Status: Design approved (brainstorming)

## 1. Scope

Build Employee module (`app/Modules/Employee/`) as the final sub-project of iHRM Phase 1 Core Platform. Covers employee master profile, contract management (lifecycle/version driven), and employee document metadata (MinIO-backed file storage), with full permission integration and test suite.

**In scope:** Employee create/update/status/manager/employment transfer, contract lifecycle (draft → activate → renew → terminate), employee document upload/replace/archive/download, employee code auto-generation via Configuration's CodeGenerationRule, MinIO file storage for documents, permission code integration with Identity module, full test suite (domain unit + application + feature).

**Out of scope:** Payroll fields, onboarding workflow, document OCR/version diff, employee self-service rules, recruitment/grades (Phase 2+), org chart visual (frontend).

## 2. Architecture

**Pattern:** Strict DDD tactical with 3 layers (mirror Identity/Organization modules).

```
Module/Employee/
  Domain/         — Pure PHP, no Laravel deps
  Application/    — Commands/Queries + Handlers, orchestrates domain
  Infrastructure/ — Eloquent, MinIO, HTTP controllers, seeders, routes
```

**Dependency:** Domain ← Application ← Infrastructure. Domain knows nothing outside itself.

## 3. Module Layout

```
app/Modules/Employee/
  Domain/
    Aggregates/Employee/
      Employee.php, EmployeeId.php, EmployeeCode.php, PersonalName.php,
      Address.php, EmployeeStatus.php, EmploymentSnapshot.php, EmployeeHistory.php
    Aggregates/Contract/
      Contract.php, ContractId.php, ContractTerm.php, DateRange.php, ContractStatus.php
    Aggregates/EmployeeDocument/
      EmployeeDocument.php, EmployeeDocumentId.php, DocumentDescriptor.php, DocumentStatus.php
    Events/
      EmployeeCreated.php, EmployeePersonalInfoUpdated.php,
      EmployeeEmploymentChanged.php, EmployeeManagerChanged.php,
      EmployeeStatusChanged.php
      ContractCreated.php, ContractActivated.php, ContractRenewed.php,
      ContractExpired.php, ContractTerminated.php
      EmployeeDocumentUploaded.php, EmployeeDocumentReplaced.php,
      EmployeeDocumentExpired.php
    Repositories/
      EmployeeRepositoryInterface.php
      ContractRepositoryInterface.php
      EmployeeDocumentRepositoryInterface.php
    Exceptions/
      EmployeeNotFoundException.php, EmployeeCodeAlreadyExistsException.php,
      InvalidEmployeeStatusTransitionException.php, EmployeeHasActiveContractsException.php,
      ContractNotFoundException.php, ContractOverlapException.php,
      ContractRenewalException.php
      EmployeeDocumentNotFoundException.php, EmployeeDocumentExpiredException.php
  Application/
    Commands/Employee/
      CreateEmployeeCommand.php, UpdateEmployeePersonalInfoCommand.php,
      TransferEmployeeCommand.php, ChangeEmployeeManagerCommand.php,
      ChangeEmployeeStatusCommand.php, LinkEmployeeToUserCommand.php
    CommandHandlers/Employee/
      (same structure as Commands)
    Commands/Contract/
      CreateContractCommand.php, ActivateContractCommand.php,
      RenewContractCommand.php, TerminateContractCommand.php
    CommandHandlers/Contract/
      (same structure as Commands)
    Commands/EmployeeDocument/
      UploadEmployeeDocumentCommand.php, ReplaceEmployeeDocumentCommand.php,
      ArchiveEmployeeDocumentCommand.php
    CommandHandlers/EmployeeDocument/
      (same structure as Commands)
    Queries/
      GetEmployeeQuery.php, ListEmployeesQuery.php
      GetEmployeeContractsQuery.php
      GetEmployeeDocumentsQuery.php
    QueryHandlers/
      (same structure as Queries)
    Services/
      EmployeeCodeGenerator.php
      EmployeeLifecyclePolicy.php
      ContractRenewalPolicy.php
  Infrastructure/
    Persistence/
      Eloquent/
        EmployeeModel.php, ContractModel.php, EmployeeDocumentModel.php
      Repositories/
        EloquentEmployeeRepository.php, EloquentContractRepository.php,
        EloquentEmployeeDocumentRepository.php
    Http/
      Controllers/
        EmployeeController.php, ContractController.php, EmployeeDocumentController.php
      Requests/
        CreateEmployeeRequest.php, UpdateEmployeePersonalInfoRequest.php,
        TransferEmployeeRequest.php, ChangeEmployeeManagerRequest.php,
        ChangeEmployeeStatusRequest.php, LinkEmployeeToUserRequest.php
        CreateContractRequest.php
        UploadEmployeeDocumentRequest.php, ReplaceEmployeeDocumentRequest.php
      Resources/
        EmployeeResource.php, ContractResource.php, EmployeeDocumentResource.php
    Seeders/
      EmployeePermissionSeeder.php, EmployeeDataSeeder.php
  Routes/api.php
```

## 4. Domain Model

### 4.1 Employee Aggregate

```
Employee {
  id: EmployeeId (UUID)
  employee_code: EmployeeCode (VO, unique, immutable)
  full_name: PersonalName (VO)
  dob: ?Carbon (immutable date)
  gender: ?string (lookup ref — "male","female","other")
  personal_email: ?string
  phone: ?string
  address: ?Address (VO)
  status: EmployeeStatus (Draft|Onboarding|Probation|Active|Suspended|Resigned|Archived)
  manager_id: ?EmployeeId (self-ref, nullable)
  branch_id: ?BranchId (VO, ref Organization)
  department_id: ?DepartmentId (VO, ref Organization)
  position_id: ?PositionId (VO, ref Organization)
  user_id: ?int (ref Identity users.id)

  static create(code, name, personalInfo?): self  — status=Draft, emits EmployeeCreated
  updatePersonalInfo(name, dob, gender, email, phone, address): void  — emits EmployeePersonalInfoUpdated
  changeEmployment(branchId, deptId, posId): void  — appends EmploymentSnapshot to history, emits EmployeeEmploymentChanged
  changeManager(managerId): void  — emits EmployeeManagerChanged
  changeStatus(newStatus, lifecyclePolicy): void  — validates state machine, emits EmployeeStatusChanged
  linkUserAccount(userId): void

  Invariants:
  - Employee code is unique (repository-level check before persist).
  - Status transition must follow lifecycle state machine.
  - Manager reference must be null or valid active employee.
  - Branch/department/position references must be active at assignment time.
  - EmployeeHistory is append-only.
}

EmployeeStatus: enum { draft, onboarding, probation, active, suspended, resigned, archived }

Transitions:
  draft → onboarding, active
  onboarding → probation
  probation → active, resigned
  active → suspended, resigned
  suspended → active
  resigned → archived

PersonalName: { firstName: string, lastName: string }
Address: { street, city, province, postalCode, country }
EmployeeCode: VO — wraps auto-generated string from CodeGenerationRule
EmployeeId: VO — wraps UUID v7 string
EmploymentSnapshot: { branchId, deptId, posId, effectiveAt }
EmployeeHistory: entity child — append-only list of EmploymentSnapshot records
```

### 4.2 Contract Aggregate

```
Contract {
  id: ContractId (UUID)
  employee_id: EmployeeId (ref)
  contract_number: string (unique, auto-gen)
  contract_type: string (lookup ref — "definite","indefinite","seasonal","probationary")
  start_date: Carbon (DateRange start)
  end_date: ?Carbon (DateRange end, required for definite/seasonal)
  sign_date: ?Carbon
  status: ContractStatus (Draft|Active|Expired|Terminated|Cancelled)
  predecessor_contract_id: ?ContractId (renewal chain)
  base_salary: ?decimal
  position_id: ?PositionId (snapshot at creation)

  static create(employeeId, type, dateRange, salary?, positionId?): self  — emits ContractCreated
  activate(): void  — must be Draft, emits ContractActivated
  renew(predecessorId, dateRange, salary?): self  — creates new successor, guard overlapping, emits ContractRenewed
  terminate(): void  — must be Active, emits ContractTerminated
  cancel(): void  — must be Draft, emits ContractTerminated
  markExpired(): void  — auto when end_date passed

  Invariants:
  - Definite/seasonal type requires end_date.
  - Renewal creates a successor contract, predecessor history unchanged.
  - One active contract per employee at a time (must terminate/expire current before new active).
}

ContractStatus: enum { draft, active, expired, terminated, cancelled }
ContractTerm: { type, startDate, endDate?, salary? }
DateRange: { start: Carbon, end: ?Carbon, includes/overlaps helpers }
ContractId: VO — wraps UUID v7 string
```

### 4.3 EmployeeDocument Aggregate

```
EmployeeDocument {
  id: EmployeeDocumentId (UUID)
  employee_id: EmployeeId (ref)
  document_type: string (lookup ref — "id_card","degree","certificate","contract_scan")
  category: ?string
  file_descriptor: DocumentDescriptor (VO)
  issue_date: ?Carbon
  expiry_date: ?Carbon
  status: DocumentStatus (Active|Archived|Expired)

  static upload(employeeId, type, descriptor, category?, issueDate?, expiryDate?): self  — emits EmployeeDocumentUploaded
  replace(newDescriptor): self  — archive current, create new Active, emits EmployeeDocumentReplaced
  archive(): void  — emits EmployeeDocumentArchived
  markExpired(): void  — emits EmployeeDocumentExpired

  Invariants:
  - Document metadata is committed only after MinIO object upload succeeds.
  - Document types configured with expiry require expiry_date.
  - Direct public file access is forbidden (download via authenticated endpoint).
}

DocumentStatus: enum { active, archived, expired }
DocumentDescriptor: { path: string, originalName: string, mime: string, size: int }
EmployeeDocumentId: VO — wraps UUID v7 string
```

## 5. Domain Events

Employee:
- `EmployeeCreated` — { employeeId, employeeCode, fullName, status }
- `EmployeePersonalInfoUpdated` — { employeeId, changedFields: [] }
- `EmployeeEmploymentChanged` — { employeeId, branchId, departmentId, positionId }
- `EmployeeManagerChanged` — { employeeId, oldManagerId, newManagerId }
- `EmployeeStatusChanged` — { employeeId, oldStatus, newStatus, reason }

Contract:
- `ContractCreated` — { contractId, employeeId, contractType, dateRange, contractNumber }
- `ContractActivated` — { contractId, employeeId, activatedAt }
- `ContractRenewed` — { newContractId, predecessorContractId, employeeId, newDateRange }
- `ContractExpired` — { contractId, employeeId, expiredAt }
- `ContractTerminated` — { contractId, employeeId, terminatedAt }

EmployeeDocument:
- `EmployeeDocumentUploaded` — { documentId, employeeId, documentType, fileDescriptor }
- `EmployeeDocumentReplaced` — { documentId, employeeId, replacementDescriptor, previousDescriptor }
- `EmployeeDocumentExpired` — { documentId, employeeId, expiredAt }

## 6. Domain Services

**EmployeeCodeGenerator:** Reads CodeGenerationRule (via Configuration module) for "employee" entity type, generates next code. Called from CreateEmployee handler before aggregate creation.

**EmployeeLifecyclePolicy:** Encapsulates status transition map (state machine). Used by Employee::changeStatus() to validate transitions.

**ContractRenewalPolicy:** Checks overlapping active contracts for same employee before renewal. Used by Contract::renew() handler.

## 7. Data Flow

### Employee Creation
1. Authz check (permission: `employee.create`)
2. Generate employee_code via EmployeeCodeGenerator (Configuration BC)
3. Validate org refs (branch/department/position) active via Organization repo read
4. Create `Employee` aggregate (status = Draft)
5. Save via `EmployeeRepository`
6. Publish domain events

### Employment Transfer
1. Authz check (permission: `employee.update`)
2. Load employee aggregate
3. Validate new org refs active
4. Call `employee.changeEmployment(branchId, deptId, posId)` → appends history
5. Save aggregate
6. Publish domain events

### Contract Activation
1. Authz check (permission: `employee.contract.activate`)
2. Load contract aggregate
3. Call `contract.activate()`
4. Save aggregate
5. Publish domain events

### Employee Document Upload
1. Authz check (permission: `employee.document.upload`)
2. Upload file to MinIO (private bucket, path: `employees/{employeeId}/documents/{documentId}`)
3. If upload succeeds, create EmployeeDocument aggregate metadata
4. Save metadata
5. Publish domain events
6. If DB save fails, best-effort delete object from MinIO

## 8. API Design

Route prefix: `/api/employee`

Protected by Sanctum auth + PermissionMiddleware (alias `permission`).

### Employee Endpoints

| Method | Path | Permission | Notes |
|--------|------|------------|-------|
| POST | /employees | employee.create | Create employee |
| GET | /employees | employee.view | List paginated, filterable |
| GET | /employees/{id} | employee.view | Single employee |
| PATCH | /employees/{id}/personal-info | employee.update | Name/dob/gender/email/phone/address |
| PATCH | /employees/{id}/employment | employee.update | Branch/dept/position change |
| PATCH | /employees/{id}/manager | employee.update | Change manager |
| PATCH | /employees/{id}/status | employee.status.change | Status transition |
| POST | /employees/{id}/link-user | employee.update | Link Identity user |

### Contract Endpoints

| Method | Path | Permission | Notes |
|--------|------|------------|-------|
| GET | /employees/{id}/contracts | employee.contract.view | List employee contracts |
| POST | /employees/{id}/contracts | employee.contract.create | Create draft contract |
| POST | /contracts/{id}/activate | employee.contract.activate | Activate contract |
| POST | /contracts/{id}/renew | employee.contract.renew | Renew (creates successor) |
| POST | /contracts/{id}/terminate | employee.contract.terminate | Terminate contract |

### Document Endpoints

| Method | Path | Permission | Notes |
|--------|------|------------|-------|
| GET | /employees/{id}/documents | employee.document.view | List employee documents |
| POST | /employees/{id}/documents | employee.document.upload | Upload (multipart + metadata) |
| POST | /documents/{id}/replace | employee.document.replace | Replace file; archives current |
| POST | /documents/{id}/archive | employee.document.archive | Archive document |
| GET | /documents/{id}/download | employee.document.download | Stream file from MinIO |

## 9. Permissions

### Permission Codes (seeded via PermissionSeeder extension)

```php
['code' => 'employee.view',              'module' => 'employee', 'action' => 'view'],
['code' => 'employee.create',            'module' => 'employee', 'action' => 'create'],
['code' => 'employee.update',            'module' => 'employee', 'action' => 'update'],
['code' => 'employee.status.change',     'module' => 'employee', 'action' => 'change_status'],
['code' => 'employee.contract.view',     'module' => 'employee', 'action' => 'view'],
['code' => 'employee.contract.create',   'module' => 'employee', 'action' => 'create'],
['code' => 'employee.contract.activate', 'module' => 'employee', 'action' => 'activate'],
['code' => 'employee.contract.renew',    'module' => 'employee', 'action' => 'renew'],
['code' => 'employee.contract.terminate','module' => 'employee', 'action' => 'terminate'],
['code' => 'employee.document.view',     'module' => 'employee', 'action' => 'view'],
['code' => 'employee.document.upload',   'module' => 'employee', 'action' => 'upload'],
['code' => 'employee.document.replace',  'module' => 'employee', 'action' => 'replace'],
['code' => 'employee.document.archive',  'module' => 'employee', 'action' => 'archive'],
['code' => 'employee.document.download', 'module' => 'employee', 'action' => 'download'],
```

### Role Assignments

| Role | Employee permissions |
|------|---------------------|
| SUPER_ADMIN | all employee.* |
| HR_MANAGER | all employee.* |
| EMPLOYEE | (future — own-profile via data scope in Phase 2) |

## 10. Database Schema

### employees table

```sql
CREATE TABLE employees (
    id              UUID PRIMARY KEY,
    employee_code   VARCHAR(50) NOT NULL UNIQUE,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    dob             DATE NULL,
    gender          VARCHAR(20) NULL,
    personal_email  VARCHAR(255) NULL,
    phone           VARCHAR(20) NULL,
    address_street  VARCHAR(255) NULL,
    address_city    VARCHAR(100) NULL,
    address_province VARCHAR(100) NULL,
    address_postal_code VARCHAR(20) NULL,
    address_country VARCHAR(100) NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'draft',
    manager_id      UUID NULL REFERENCES employees(id),
    branch_id       UUID NULL REFERENCES branches(id),
    department_id   UUID NULL REFERENCES departments(id),
    position_id     UUID NULL REFERENCES positions(id),
    user_id         BIGINT NULL REFERENCES users(id),
    created_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_employees_branch_id ON employees(branch_id);
CREATE INDEX idx_employees_department_id ON employees(department_id);
CREATE INDEX idx_employees_position_id ON employees(position_id);
CREATE INDEX idx_employees_manager_id ON employees(manager_id);
CREATE INDEX idx_employees_user_id ON employees(user_id);
```

### employee_history table

```sql
CREATE TABLE employee_history (
    id              BIGSERIAL PRIMARY KEY,
    employee_id     UUID NOT NULL REFERENCES employees(id),
    branch_id       UUID NULL REFERENCES branches(id),
    department_id   UUID NULL REFERENCES departments(id),
    position_id     UUID NULL REFERENCES positions(id),
    effective_at    TIMESTAMP NOT NULL DEFAULT NOW(),
    created_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_employee_history_employee_id ON employee_history(employee_id);
```

### employee_contracts table

```sql
CREATE TABLE employee_contracts (
    id                      UUID PRIMARY KEY,
    employee_id             UUID NOT NULL REFERENCES employees(id),
    contract_number         VARCHAR(50) NOT NULL UNIQUE,
    contract_type           VARCHAR(50) NOT NULL,
    start_date              DATE NOT NULL,
    end_date                DATE NULL,
    sign_date               DATE NULL,
    status                  VARCHAR(20) NOT NULL DEFAULT 'draft',
    predecessor_contract_id UUID NULL REFERENCES employee_contracts(id),
    base_salary             DECIMAL(15,2) NULL,
    position_id             UUID NULL REFERENCES positions(id),
    created_at              TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_employee_contracts_employee_id ON employee_contracts(employee_id);
CREATE INDEX idx_employee_contracts_status ON employee_contracts(status);
```

### employee_documents table

```sql
CREATE TABLE employee_documents (
    id              UUID PRIMARY KEY,
    employee_id     UUID NOT NULL REFERENCES employees(id),
    document_type   VARCHAR(50) NOT NULL,
    category        VARCHAR(100) NULL,
    file_path       TEXT NOT NULL,
    file_original_name VARCHAR(255) NOT NULL,
    file_mime       VARCHAR(100) NOT NULL,
    file_size       BIGINT NOT NULL,
    issue_date      DATE NULL,
    expiry_date     DATE NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_employee_documents_employee_id ON employee_documents(employee_id);
CREATE INDEX idx_employee_documents_status ON employee_documents(status);
```

## 11. MinIO Integration

- MinIO client already configured in Laravel (Filesystem disk `s3` or custom `minio` disk).
- Document upload path: `employees/{employeeId}/documents/{documentId}_{originalFilename}`
- Bucket: `ihrm-employee-docs` (private)
- Download through authenticated endpoint `GET /documents/{id}/download`:
  1. Authz check
  2. Load document metadata from DB
  3. Generate presigned URL or stream file (signed URL approach preferred)
  4. Return file download response
- No direct public URL exposure.

## 12. Integration Points

- **Identity module**: AuthorizationService (permission checks), Sanctum auth, PermissionSeeder/RoleSeeder extend
- **Organization module**: Branch/Department/Position ID validation (active checks via repo reads)
- **Configuration module**: EmployeeCodeGenerator reads CodeGenerationRule; gender/contract type/document type lookups
- **Audit module**: subscribes to all Employee domain events
- **Identity users table**: link-user uses user_id FK; no Employee aggregate modification to Identity side

## 13. Testing Strategy

| Layer | Approach | Count |
|-------|----------|-------|
| Domain unit | Pure PHP, no Laravel boot. Test status machine, contract invariants, document expiry. | ~20 |
| Application | CommandHandlers with fake repos. | ~10 |
| Feature HTTP | Full API test (DB + MinIO). Each endpoint + permission enforcement (403). | ~20 |
| **Total** | | **~50** |

Key domain test cases:
- `Employee::changeStatus()` invalid transition → throws InvalidEmployeeStatusTransitionException
- `Contract::activate()` with overlapping active contract → throws ContractOverlapException
- `EmployeeDocument::replace()` archives current + creates new Active
- `EmployeeCodeAlreadyExistsException` when duplicate code on save

## 14. Acceptance Criteria

1. All 21+ API endpoints functional and documented.
2. Employee status transitions follow lifecycle state machine (422 on invalid transition).
3. Contract overlap guard blocks double-active contracts (422).
4. Document upload persists file to MinIO + metadata to DB.
5. Document download streams from MinIO with auth check.
6. All employee.* permissions exist after seeding.
7. HR_MANAGER role has full employee.* permission set.
8. All tests pass (unit + application + feature).
9. Audit events emitted for every mutation.
10. PermissionMiddleware returns 403 for unauthorized requests.
11. Module structure matches Identity/Organization modules.
12. MinIO bucket `ihrm-employee-docs` created (or auto-create on first upload).

## 15. Implementation Order

1. Migration files (4: employees, employee_history, employee_contracts, employee_documents) + indexes
2. Eloquent models (EmployeeModel, ContractModel, EmployeeDocumentModel)
3. Domain layer: value objects, aggregates, events, exceptions, repository interfaces
4. Application layer: domain services + command/query classes + handlers
5. Infrastructure persistence: Eloquent repositories
6. Infrastructure HTTP: controllers, FormRequests, Resources, routes (in Employee/Routes/api.php)
7. Routes: update `src/backend/routes/api.php` to load Employee routes
8. Seeders: update Identity's PermissionSeeder/RoleSeeder + EmployeePermissionSeeder + EmployeeDataSeeder
9. MinIO: ensure bucket creation in docker setup or service provider
10. Test suite: domain unit → application integration → feature HTTP + MinIO integration
11. Module README

## 16. Dependencies

- **Identity module**: permission checks, Sanctum auth
- **Organization module**: branch/department/position active validation (repo read)
- **Configuration module**: code generation, lookups
- **Audit module**: event subscription
- **Infrastructure**: PostgreSQL 16, MinIO S3-compatible storage

## 17. Risks

- MinIO not configured in dev environment → must add bucket setup step in docker/compose or service provider auto-create.
- `manager_id` self-FK on employees table → cycle detection deferred to app-layer (Phase 1 OK, small org).
- Employee code generation depends on Configuration module's CodeGenerationRule — ensure Configuration module seeds default rules for "employee" entity type.
- Contract overlapping check only works within same employee, not cross-employee (Phase 1 OK).
