# Phase 2 Workflow BC Design

Version: 0.1
Date: 2026-07-02
Status: Design approved (brainstorming)

## 1. Scope

Build Workflow module (`app/Modules/Workflow/`) as the reusable Phase 2 approval engine. It supports multi-step sequential approval templates and workflow request instances linked to any business subject via `subject_type` and `subject_id`.

**In scope:** `WorkflowTemplate`, `WorkflowTemplateStep`, `WorkflowRequest`, `WorkflowAction`; ordered multi-step approval; approve/reject/return-for-edit/cancel actions; action history; active template listing; simple assignee matching by role, department, or specific user; events for downstream audit/notification; permissions and tests.

**Out of scope:** migrating Leave or Attendance to Workflow, notifications delivery, escalation/timeouts, delegated approvers, complex condition DSL, amount/duration-based conditional routing, parallel approvals, quorum approvals, SLA timers, external BPMN-style engine.

Consumer migration is intentionally deferred. Leave and Attendance keep current inline approvals until a dedicated integration pass.

## 2. Architecture

Strict DDD tactical pattern, mirroring existing Phase 2 modules.

```
Module/Workflow/
  Domain/         — pure PHP aggregates, VOs, events, exceptions
  Application/    — commands, handlers, queries
  Infrastructure/ — Eloquent, HTTP, routes
```

Workflow is a service-provider BC. It does not own Leave, Attendance, Payroll, or any business subject aggregate. It only tracks approval state for `(subject_type, subject_id)`.

## 3. Module Layout

```
app/Modules/Workflow/
  Domain/
    Aggregates/
      WorkflowTemplate/
        WorkflowTemplate.php
        WorkflowTemplateId.php
        WorkflowStep.php
        WorkflowStepId.php
      WorkflowRequest/
        WorkflowRequest.php
        WorkflowRequestId.php
        WorkflowAction.php
        WorkflowActionId.php
    ValueObjects/
      RequestStatus.php
      WorkflowActionType.php
      AssigneeType.php
      SubjectType.php
    Events/
      WorkflowRequestStarted.php
      WorkflowStepCompleted.php
      WorkflowApproved.php
      WorkflowRejected.php
      WorkflowCancelled.php
      WorkflowReturnedForEdit.php
    Repositories/
      WorkflowTemplateRepositoryInterface.php
      WorkflowRequestRepositoryInterface.php
    Exceptions/
      WorkflowTemplateNotFoundException.php
      WorkflowRequestNotFoundException.php
      WorkflowStepNotFoundException.php
      WorkflowActorNotAllowedException.php
      InvalidWorkflowTransitionException.php
  Application/
    Commands/
      CreateWorkflowTemplateCommand.php
      StartWorkflowRequestCommand.php
      ApproveWorkflowStepCommand.php
      RejectWorkflowStepCommand.php
      ReturnWorkflowForEditCommand.php
      CancelWorkflowRequestCommand.php
    CommandHandlers/
      CreateWorkflowTemplateHandler.php
      StartWorkflowRequestHandler.php
      ApproveWorkflowStepHandler.php
      RejectWorkflowStepHandler.php
      ReturnWorkflowForEditHandler.php
      CancelWorkflowRequestHandler.php
    Queries/
      GetWorkflowTemplateQuery.php
      ListWorkflowTemplatesQuery.php
      GetWorkflowRequestQuery.php
      ListWorkflowRequestsQuery.php
    QueryHandlers/
      ...
  Infrastructure/
    Persistence/
      Eloquent/
        WorkflowTemplateModel.php
        WorkflowTemplateStepModel.php
        WorkflowRequestModel.php
        WorkflowRequestActionModel.php
      Repositories/
        EloquentWorkflowTemplateRepository.php
        EloquentWorkflowRequestRepository.php
    Http/
      Controllers/
        WorkflowTemplateController.php
        WorkflowRequestController.php
      Requests/
        CreateWorkflowTemplateRequest.php
        StartWorkflowRequestRequest.php
        ApproveWorkflowStepRequest.php
        RejectWorkflowStepRequest.php
        ReturnWorkflowForEditRequest.php
        CancelWorkflowRequestRequest.php
      Resources/
        WorkflowTemplateResource.php
        WorkflowRequestResource.php
        WorkflowActionResource.php
  Routes/api.php
```

## 4. Schema

### `workflow_templates`

| Column | Type | Notes |
|---|---|---|
| id | UUID | PK |
| code | varchar(80) unique | stable template code, e.g. `leave.default` |
| name | varchar(255) | display name |
| description | text nullable | optional |
| active | boolean | default true |
| created_at / updated_at | timestamps | |

Indexes: `active`.

### `workflow_template_steps`

| Column | Type | Notes |
|---|---|---|
| id | UUID | PK |
| workflow_template_id | UUID | FK → workflow_templates |
| step_order | integer | 1-based order |
| name | varchar(255) | e.g. Manager Approval |
| assignee_type | varchar(30) | role, department, specific_user |
| assignee_id | UUID nullable | role_id / department_id / user_id depending type |
| condition | JSONB nullable | empty/null means always active |
| created_at / updated_at | timestamps | |

Unique: `(workflow_template_id, step_order)`. Index: `(workflow_template_id, step_order)`.

### `workflow_requests`

| Column | Type | Notes |
|---|---|---|
| id | UUID | PK |
| workflow_template_id | UUID | FK → workflow_templates |
| subject_type | varchar(80) | e.g. leave_request, attendance_adjustment, payroll_run |
| subject_id | UUID | business aggregate id |
| status | varchar(30) | pending, in_review, approved, rejected, cancelled, returned |
| current_step | integer | 1-based; null only when final/cancelled/rejected |
| submitted_by | UUID | user id |
| created_at / updated_at | timestamps | |

Indexes: `(subject_type, subject_id)`, `workflow_template_id`, `status`, `(submitted_by, status)`.

### `workflow_request_actions`

| Column | Type | Notes |
|---|---|---|
| id | UUID | PK |
| workflow_request_id | UUID | FK → workflow_requests |
| step_order | integer | step decided |
| action | varchar(30) | approve, reject, return_for_edit, cancel |
| actor_id | UUID | user id |
| comment | text nullable | required for reject/return |
| metadata | JSONB | default `{}` |
| created_at | timestamp | action time |

Index: `(workflow_request_id, created_at)`.

## 5. Domain Model

### WorkflowTemplate

Fields: `id`, `code`, `name`, `description`, `active`, ordered `steps`.

Invariants:
- At least one step.
- Step order starts at 1 and has no gaps.
- Step orders unique within template.
- Inactive templates cannot start new requests.
- Template edits affect new requests only; existing requests keep their copied step state through request actions/current_step.

Methods:
- `activate()` / `deactivate()`
- `steps()` returns ordered `WorkflowStep[]`
- `firstStep()`
- `nextStepAfter(int $stepOrder)`

### WorkflowStep

Fields: `id`, `stepOrder`, `name`, `assigneeType`, `assigneeId`, `condition`.

`AssigneeType` enum:
- `role`
- `department`
- `specific_user`

Condition handling is intentionally small: null/empty condition means active. Non-empty JSON is stored but not evaluated in Phase 2; a future integration pass can evaluate it before request creation.

### WorkflowRequest

Fields: `id`, `workflowTemplateId`, `subjectType`, `subjectId`, `status`, `currentStep`, `submittedBy`, `actions`.

Status machine:

```
submit -> pending
pending -> in_review             (start first step)
in_review -> in_review           (approve non-final step)
in_review -> approved            (approve final step)
in_review -> rejected            (reject any step)
in_review -> returned            (return-for-edit any step)
pending|in_review|returned -> cancelled
returned -> in_review            (resubmit through start command)
```

Domain methods:
- `start(int $firstStepOrder)` — pending → in_review
- `approveStep(actorId, stepOrder, isFinalStep, comment?)`
- `rejectStep(actorId, stepOrder, comment)`
- `returnForEdit(actorId, stepOrder, comment)`
- `cancel(actorId, comment?)`

Invariants:
- Only current step can be acted on.
- Final approval sets `status=approved` and `current_step=null`.
- Reject sets `status=rejected`, `current_step=null`.
- Return-for-edit sets `status=returned`, `current_step=null`.
- Cancel sets `status=cancelled`, `current_step=null`.
- Every state transition appends a `WorkflowAction`.

### WorkflowAction

Fields: `id`, `workflowRequestId`, `stepOrder`, `action`, `actorId`, `comment`, `metadata`, `createdAt`.

Action log is append-only.

## 6. Actor Authorization

Workflow BC performs step-level actor matching in addition to route permission checks.

Rules:
- `specific_user`: `actor_id == assignee_id`
- `role`: actor has active role matching `assignee_id`
- `department`: actor belongs to employee in department matching `assignee_id`

If actor does not match: `WorkflowActorNotAllowedException` → HTTP 403.

Step-level matching depends on Identity/Employee read models in Infrastructure. Domain receives only a boolean/actor context from Application.

## 7. Application Layer

### Commands

| Command | Behavior |
|---|---|
| `CreateWorkflowTemplateCommand` | Creates active template + ordered steps. |
| `StartWorkflowRequestCommand` | Finds active template, creates pending request, starts first step (`in_review`). |
| `ApproveWorkflowStepCommand` | Checks actor eligibility, records approve action, advances to next step or approved. |
| `RejectWorkflowStepCommand` | Checks actor eligibility, records reject action, sets rejected. |
| `ReturnWorkflowForEditCommand` | Checks actor eligibility, records return action, sets returned. |
| `CancelWorkflowRequestCommand` | Records cancel action, sets cancelled. |

### Queries

| Query | Behavior |
|---|---|
| `ListWorkflowTemplatesQuery` | Active templates by default; optional include inactive. |
| `GetWorkflowTemplateQuery` | Template + ordered steps. |
| `ListWorkflowRequestsQuery` | Filter by status, subject_type, subject_id, submitted_by. |
| `GetWorkflowRequestQuery` | Request + action history. |

### Transactions

Each command that mutates workflow state runs in a DB transaction:
1. Load request/template.
2. Verify status/current step/actor.
3. Mutate request and append action.
4. Save request + action.
5. Dispatch event after commit.

## 8. HTTP API

Routes under `Route::prefix('v1')->middleware('auth:sanctum')`.

| Method | Path | Permission |
|---|---|---|
| POST | `/workflow-templates` | `workflow.template.create` |
| GET | `/workflow-templates` | `workflow.template.view` |
| GET | `/workflow-templates/{id}` | `workflow.template.view` |
| POST | `/workflow-requests` | `workflow.request.start` |
| GET | `/workflow-requests` | `workflow.request.view` |
| GET | `/workflow-requests/{id}` | `workflow.request.view` |
| POST | `/workflow-requests/{id}/approve` | `workflow.request.approve` |
| POST | `/workflow-requests/{id}/reject` | `workflow.request.reject` |
| POST | `/workflow-requests/{id}/return-for-edit` | `workflow.request.return` |
| POST | `/workflow-requests/{id}/cancel` | `workflow.request.cancel` |

### Request Validation

`CreateWorkflowTemplateRequest`:
- `code`: required, string, max 80, unique
- `name`: required, string, max 255
- `description`: nullable string
- `steps`: required array min 1
- `steps.*.step_order`: required integer min 1
- `steps.*.name`: required string max 255
- `steps.*.assignee_type`: required in `role,department,specific_user`
- `steps.*.assignee_id`: nullable uuid
- `steps.*.condition`: nullable array

Decision requests:
- `comment`: nullable for approve/cancel; required for reject/return-for-edit.

## 9. Permissions

```php
['workflow.template.create', 'workflow-template', 'create'],
['workflow.template.view',   'workflow-template', 'view'],
['workflow.request.start',   'workflow-request',  'start'],
['workflow.request.view',    'workflow-request',  'view'],
['workflow.request.approve', 'workflow-request',  'approve'],
['workflow.request.reject',  'workflow-request',  'reject'],
['workflow.request.return',  'workflow-request',  'return'],
['workflow.request.cancel',  'workflow-request',  'cancel'],
```

Grant all `workflow.*` to `SUPER_ADMIN` and `HR_MANAGER`.

## 10. Events

- `WorkflowRequestStarted` — request entered `in_review`.
- `WorkflowStepCompleted` — a non-final step approved.
- `WorkflowApproved` — final step approved.
- `WorkflowRejected` — any step rejected.
- `WorkflowReturnedForEdit` — any step returned.
- `WorkflowCancelled` — request cancelled.

Audit listens to these events through the existing Audit listener pattern. Notification delivery is deferred.

## 11. Testing Strategy

| Layer | Coverage |
|---|---|
| Domain unit | Template step ordering, invalid gaps, request status machine, final approval, reject, return, cancel, illegal transitions. |
| Application unit | Start request, approve multi-step request, reject, return-for-edit, cancel, actor mismatch. |
| Feature HTTP | 401 unauthenticated, 403 missing permission, create template, start request, approve two-step request to approved, reject request, return-for-edit, cancel request. |

Key test cases:
1. Template with step orders `1,2` is valid.
2. Template with duplicate/gapped step order throws 422/domain exception.
3. Start request sets `status=in_review`, `current_step=1`.
4. Approving step 1 on two-step request sets `current_step=2`, status remains `in_review`.
5. Approving final step sets `status=approved`, `current_step=null`.
6. Reject from any step sets `status=rejected`, action logged.
7. Return from any step sets `status=returned`, action logged.
8. Cancel pending/in_review/returned sets `status=cancelled`.
9. Acting on non-current step throws 422.
10. Actor mismatch throws 403.

## 12. Acceptance Criteria

1. Workflow templates can be created with 1+ ordered steps.
2. Invalid template step order is rejected.
3. Active templates can be listed and fetched with steps.
4. Workflow requests can be started for arbitrary `(subject_type, subject_id)`.
5. Starting a request activates step 1 and status `in_review`.
6. Approving non-final step advances to next step and logs action.
7. Approving final step sets request `approved` and logs action.
8. Rejecting any active step sets request `rejected` and logs action.
9. Returning any active step sets request `returned` and logs action.
10. Cancelling allowed states sets request `cancelled` and logs action.
11. Actor mismatch for a step returns 403.
12. Illegal state/current-step transitions return 422.
13. All `workflow.*` permissions are seeded and granted to `SUPER_ADMIN` and `HR_MANAGER`.
14. All Workflow tests pass; full backend suite is green.
15. Module structure matches existing Phase 2 modules.

## 13. Dependencies

- **Identity** — role lookup and route permission checks.
- **Employee/Organization** — department membership check for department assignees.
- **Audit** — consumes workflow events.
- **Notification** — later consumes workflow events; no dependency in this module.

## 14. Risks and Simplifications

- **Conditions:** JSON `condition` is stored but not evaluated in Phase 2. This keeps schema compatible without inventing a fragile expression engine. Upgrade path: add `WorkflowConditionEvaluator` application service.
- **Delegation:** Explicitly deferred. Upgrade path: check delegated actor before actor mismatch exception.
- **Parallel approval:** Not supported. Sequential steps only.
- **Consumer sync:** Workflow approves workflow requests only; it does not mutate Leave/Attendance/Payroll aggregates. Each consumer migration should subscribe or call Workflow in a separate integration pass.

## 15. Implementation Order

1. Migrations for templates, steps, requests, actions.
2. Eloquent models and repository mappings.
3. Domain value objects, events, exceptions.
4. Domain aggregates and state machine tests.
5. Application commands/handlers/queries.
6. HTTP requests/resources/controllers/routes.
7. Permissions and roles.
8. Feature tests.
9. README.
10. Full suite and spec checklist.
