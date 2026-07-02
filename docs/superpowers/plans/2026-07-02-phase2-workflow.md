# Phase 2 Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the reusable Phase 2 Workflow module with multi-step sequential approval templates, request instances, action history, and a full test suite.

**Architecture:** Strict DDD tactical structure under `src/backend/app/Modules/Workflow`: Domain is pure PHP, Application orchestrates commands/handlers/queries, Infrastructure owns Eloquent/HTTP/routes. Multi-step sequential approval (not parallel, no escalation, no delegation).

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL 16 UUIDs, Sanctum, Eloquent repositories, PHPUnit.

---

## File Map

- `src/backend/app/Modules/Workflow/Domain/**`: aggregates, VOs, events, exceptions, repo contracts.
- `src/backend/app/Modules/Workflow/Application/**`: commands, handlers, queries.
- `src/backend/app/Modules/Workflow/Infrastructure/**`: Eloquent models/repos, HTTP, routes.
- `src/backend/app/Modules/Workflow/Routes/api.php`
- `src/backend/database/migrations/2026_07_02_07000*_create_workflow_*.php`
- `src/backend/app/Providers/AppServiceProvider.php`
- `src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php`, `RoleSeeder.php`
- `src/backend/routes/api.php`
- `src/backend/tests/Unit/Modules/Workflow/`
- `src/backend/tests/Feature/Modules/Workflow/`
- `src/backend/app/Modules/Workflow/README.md`

---

### Task 1: Migrations & Eloquent models

**Files:**
- Create: `src/backend/database/migrations/2026_07_02_070001_create_workflow_templates_table.php`
- Create: `src/backend/database/migrations/2026_07_02_070002_create_workflow_template_steps_table.php`
- Create: `src/backend/database/migrations/2026_07_02_070003_create_workflow_requests_table.php`
- Create: `src/backend/database/migrations/2026_07_02_070004_create_workflow_request_actions_table.php`
- Create: `src/backend/app/Modules/Workflow/Infrastructure/Persistence/Eloquent/WorkflowTemplateModel.php`
- Create: `src/backend/app/Modules/Workflow/Infrastructure/Persistence/Eloquent/WorkflowTemplateStepModel.php`
- Create: `src/backend/app/Modules/Workflow/Infrastructure/Persistence/Eloquent/WorkflowRequestModel.php`
- Create: `src/backend/app/Modules/Workflow/Infrastructure/Persistence/Eloquent/WorkflowRequestActionModel.php`

- [ ] **Step 1: Create migrations**
  - `workflow_templates`: UUID id, unique `code`, `name`, nullable `description`, `active` default true, timestamps. Index: `active`.
  - `workflow_template_steps`: UUID id, FK `workflow_template_id`, `step_order` int, `name`, `assignee_type` varchar(30), nullable `assignee_id` uuid, nullable `condition` jsonb, timestamps. Unique: `(workflow_template_id, step_order)`. Index: `(workflow_template_id, step_order)`.
  - `workflow_requests`: UUID id, FK `workflow_template_id`, `subject_type` varchar(80), `subject_id` uuid, `status` varchar(30), nullable `current_step` int, `submitted_by` uuid, timestamps. Indexes: `(subject_type,subject_id)`, `status`, `(submitted_by,status)`.
  - `workflow_request_actions`: UUID id, FK `workflow_request_id`, `step_order` int, `action` varchar(30), `actor_id` uuid, nullable `comment`, `metadata` jsonb default `{}`, `created_at` timestamp. Index: `(workflow_request_id, created_at)`.

- [ ] **Step 2: Create Eloquent models**
  Match existing Attendance/Leave model style: `$keyType = 'string'`, `$incrementing = false`, `$casts` for bool/json/date/datetime.

- [ ] **Step 3: Run migrations**
  ```bash
  docker compose run --rm app php artisan migrate:fresh --seed
  ```
  Expected: PASS; `workflow_*` tables exist.

- [ ] **Step 4: Commit**
  ```bash
  git add src/backend/database/migrations/2026_07_02_07000*_create_workflow_*.php src/backend/app/Modules/Workflow/Infrastructure/Persistence/Eloquent/
  git commit -m "feat(workflow): add schema"
  ```

---

### Task 2: Domain value objects, events, exceptions

**Files:**
- Create: `src/backend/app/Modules/Workflow/Domain/ValueObjects/RequestStatus.php`
- Create: `src/backend/app/Modules/Workflow/Domain/ValueObjects/WorkflowActionType.php`
- Create: `src/backend/app/Modules/Workflow/Domain/ValueObjects/AssigneeType.php`
- Create: `src/backend/app/Modules/Workflow/Domain/Events/*.php` (6 events)
- Create: `src/backend/app/Modules/Workflow/Domain/Exceptions/*.php` (5 exceptions)

- [ ] **Step 1: Create value objects**
  `RequestStatus`: enum `pending|in_review|approved|rejected|cancelled|returned`.
  `WorkflowActionType`: enum `approve|reject|return_for_edit|cancel`.
  `AssigneeType`: enum `role|department|specific_user`.

- [ ] **Step 2: Create events**
  Use same pattern as Leave module: simple DTO-like classes with `public readonly array $payload`.

- [ ] **Step 3: Create exceptions**
  Domain exceptions extending `DomainException`. Map to HTTP status in AppServiceProvider if handler pattern exists; otherwise handler throws.

- [ ] **Step 4: Commit**
  ```bash
  git add src/backend/app/Modules/Workflow/Domain/ValueObjects/ src/backend/app/Modules/Workflow/Domain/Events/ src/backend/app/Modules/Workflow/Domain/Exceptions/
  git commit -m "feat(workflow): add domain primitives"
  ```

---

### Task 3: Domain aggregates + tests

**Files:**
- Create: `src/backend/app/Modules/Workflow/Domain/Aggregates/WorkflowTemplate/WorkflowTemplate.php`
- Create: `src/backend/app/Modules/Workflow/Domain/Aggregates/WorkflowTemplate/WorkflowTemplateId.php`
- Create: `src/backend/app/Modules/Workflow/Domain/Aggregates/WorkflowTemplate/WorkflowStep.php`
- Create: `src/backend/app/Modules/Workflow/Domain/Aggregates/WorkflowTemplate/WorkflowStepId.php`
- Create: `src/backend/app/Modules/Workflow/Domain/Aggregates/WorkflowRequest/WorkflowRequest.php`
- Create: `src/backend/app/Modules/Workflow/Domain/Aggregates/WorkflowRequest/WorkflowRequestId.php`
- Create: `src/backend/app/Modules/Workflow/Domain/Aggregates/WorkflowRequest/WorkflowAction.php`
- Create: `src/backend/app/Modules/Workflow/Domain/Aggregates/WorkflowRequest/WorkflowActionId.php`
- Create: `src/backend/app/Modules/Workflow/Domain/Repositories/WorkflowTemplateRepositoryInterface.php`
- Create: `src/backend/app/Modules/Workflow/Domain/Repositories/WorkflowRequestRepositoryInterface.php`
- Test: `src/backend/tests/Unit/Modules/Workflow/Domain/WorkflowTemplateTest.php`
- Test: `src/backend/tests/Unit/Modules/Workflow/Domain/WorkflowRequestStateMachineTest.php`

- [ ] **Step 1: Write aggregate tests first (TDD)**

  `WorkflowTemplateTest`:
  - create template with steps 1,2,3 → steps ordered correctly
  - template with gap (1,3) → throws
  - template with single step → valid
  - deactivate template → isActive() false
  - `nextStepAfter(1)` → step 2, `nextStepAfter(3)` → null

  `WorkflowRequestStateMachineTest`:
  - submitted → pending
  - start → pending→in_review, current_step=1
  - approve non-final step → in_review, current_step advances
  - approve final step → approved, current_step=null
  - reject → rejected, current_step=null
  - return → returned, current_step=null
  - cancel pending/in_review/returned → cancelled
  - cancel already approved → throws
  - act on non-current step → throws
  - action logged on every transition

- [ ] **Step 2: Implement aggregates**

  `WorkflowTemplate`:
  - Constructor takes id, code, name, steps, active=true
  - Validates step order (no gaps, starts at 1)
  - `activate/deactivate`, `firstStep()`, `nextStepAfter(int)`

  `WorkflowStep`:
  - id, stepOrder, name, assigneeType, assigneeId, condition

  `WorkflowRequest`:
  - id, templateId, subjectType, subjectId, status, currentStep, submittedBy, actions
  - `start(firstStepOrder)` → pending→in_review, appends action
  - `approveStep(actorId, stepOrder, isFinal)` → mutates, appends action, emits event
  - `rejectStep(actorId, stepOrder, comment)` → mutates, appends action
  - `returnForEdit(actorId, stepOrder, comment)` → mutates, appends action
  - `cancel(actorId)` → mutates, appends action
  - All guards throw exceptions for invalid transitions

- [ ] **Step 3: Create repository interfaces**

  Standard `findById`, `save`, and specific queries (findBySubject, findByStatus, etc.).

- [ ] **Step 4: Run tests**
  ```bash
  docker compose run --rm app php artisan test tests/Unit/Modules/Workflow/Domain --compact
  ```
  Expected: PASS.

- [ ] **Step 5: Commit**
  ```bash
  git add src/backend/app/Modules/Workflow/Domain/Aggregates/ src/backend/app/Modules/Workflow/Domain/Repositories/ src/backend/tests/Unit/Modules/Workflow/Domain/
  git commit -m "feat(workflow): add domain aggregates"
  ```

---

### Task 4: Eloquent repositories + DI

**Files:**
- Create: `src/backend/app/Modules/Workflow/Infrastructure/Persistence/Repositories/EloquentWorkflowTemplateRepository.php`
- Create: `src/backend/app/Modules/Workflow/Infrastructure/Persistence/Repositories/EloquentWorkflowRequestRepository.php`
- Modify: `src/backend/app/Providers/AppServiceProvider.php`
- Test: `src/backend/tests/Unit/Modules/Workflow/Infrastructure/WorkflowRepositoryTest.php`

- [ ] **Step 1: Implement Eloquent repos**

  Map Eloquent models ↔ domain aggregates. Template repo loads steps eagerly. Request repo loads actions eagerly.

- [ ] **Step 2: Register bindings**

  Add bindings for both interfaces in `AppServiceProvider` following existing pattern.

- [ ] **Step 3: Run test**
  ```bash
  docker compose run --rm app php artisan test tests/Unit/Modules/Workflow/Infrastructure/WorkflowRepositoryTest.php --compact
  ```
  Expected: PASS.

- [ ] **Step 4: Commit**
  ```bash
  git add src/backend/app/Modules/Workflow/Infrastructure/Persistence/Repositories/ src/backend/app/Providers/AppServiceProvider.php src/backend/tests/Unit/Modules/Workflow/Infrastructure/
  git commit -m "feat(workflow): add repositories"
  ```

---

### Task 5: Application commands/handlers

**Files:**
- Create: `src/backend/app/Modules/Workflow/Application/Commands/CreateWorkflowTemplateCommand.php`, `StartWorkflowRequestCommand.php`, `ApproveWorkflowStepCommand.php`, `RejectWorkflowStepCommand.php`, `ReturnWorkflowForEditCommand.php`, `CancelWorkflowRequestCommand.php`
- Create: corresponding handlers under `Application/CommandHandlers/`
- Create: queries `GetWorkflowTemplateQuery.php`, `ListWorkflowTemplatesQuery.php`, `GetWorkflowRequestQuery.php`, `ListWorkflowRequestsQuery.php`
- Create: corresponding query handlers under `Application/QueryHandlers/`
- Test: `src/backend/tests/Unit/Modules/Workflow/Application/WorkflowHandlerTest.php`

- [ ] **Step 1: Create command DTOs**

  Commands are readonly DTOs with typed properties. Keep thin.

- [ ] **Step 2: Create handlers**

  Key handler behaviors:
  - `StartWorkflowRequestHandler`: load active template → create request → call `start()` → save
  - `ApproveWorkflowStepHandler`: load request → verify actor eligibility (call service/repo) → call `approveStep()` → save request + action
  - `RejectWorkflowStepHandler`: same pattern → `rejectStep()`
  - `ReturnWorkflowForEditHandler`: same pattern → `returnForEdit()`
  - `CancelWorkflowRequestHandler`: → `cancel()`
  - `CreateWorkflowTemplateHandler`: validate steps → create template → save

- [ ] **Step 3: Create query handlers**

  Simple read-model queries via repositories.

- [ ] **Step 4: Run tests**
  ```bash
  docker compose run --rm app php artisan test tests/Unit/Modules/Workflow/Application/WorkflowHandlerTest.php --compact
  ```

- [ ] **Step 5: Commit**
  ```bash
  git add src/backend/app/Modules/Workflow/Application/ src/backend/tests/Unit/Modules/Workflow/Application/
  git commit -m "feat(workflow): add application layer"
  ```

---

### Task 6: HTTP API

**Files:**
- Create: `src/backend/app/Modules/Workflow/Infrastructure/Http/Controllers/WorkflowTemplateController.php`, `WorkflowRequestController.php`
- Create: FormRequests: `CreateWorkflowTemplateRequest.php`, `StartWorkflowRequestRequest.php`, `ApproveWorkflowStepRequest.php`, `RejectWorkflowStepRequest.php`, `ReturnWorkflowForEditRequest.php`, `CancelWorkflowRequestRequest.php`
- Create: Resources: `WorkflowTemplateResource.php`, `WorkflowRequestResource.php`, `WorkflowActionResource.php`
- Create: `src/backend/app/Modules/Workflow/Routes/api.php`
- Modify: `src/backend/routes/api.php`

- [ ] **Step 1: Create resources and requests**

  Match existing module resource pattern.

- [ ] **Step 2: Create controllers**

  Thin controllers. Delegate to handlers/queries. Map exceptions to HTTP codes:
  - WorkflowTemplateNotFoundException → 404
  - WorkflowRequestNotFoundException → 404
  - WorkflowStepNotFoundException → 404
  - WorkflowActorNotAllowedException → 403
  - InvalidWorkflowTransitionException → 422

- [ ] **Step 3: Add routes**

  Routes under `Route::prefix('v1')->middleware('auth:sanctum')` with permission middleware per spec.

- [ ] **Step 4: Wire in `routes/api.php`**

- [ ] **Step 5: Verify**
  ```bash
  docker compose run --rm app php artisan route:list | grep workflow
  ```
  Expected: all routes listed.

- [ ] **Step 6: Commit**
  ```bash
  git add src/backend/app/Modules/Workflow/Infrastructure/Http/ src/backend/app/Modules/Workflow/Routes/ src/backend/routes/api.php
  git commit -m "feat(workflow): add HTTP API"
  ```

---

### Task 7: Permissions

**Files:**
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php`
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/RoleSeeder.php`

- [ ] **Step 1: Add permissions**
  ```php
  'workflow.template.create', 'workflow.template.view',
  'workflow.request.start', 'workflow.request.view',
  'workflow.request.approve', 'workflow.request.reject',
  'workflow.request.return', 'workflow.request.cancel',
  ```

- [ ] **Step 2: Grant to roles**

  All `workflow.*` to `SUPER_ADMIN` and `HR_MANAGER`.

- [ ] **Step 3: Commit**
  ```bash
  git add src/backend/app/Modules/Identity/Infrastructure/Seeders/
  git commit -m "feat(workflow): add permissions"
  ```

---

### Task 8: Feature tests + README

**Files:**
- Create: `src/backend/tests/Feature/Modules/Workflow/WorkflowApiTest.php`
- Create: `src/backend/app/Modules/Workflow/README.md`

- [ ] **Step 1: Write feature tests**

  Key scenarios:
  - unauthenticated → 401
  - missing permission → 403
  - create template with steps → 201
  - create template with invalid steps (gap) → 422
  - start request → 201, in_review
  - approve non-final step → advances
  - approve final step → approved
  - reject current step → rejected
  - return current step → returned
  - cancel → cancelled
  - actor mismatch → 403
  - illegal action on wrong status → 422

- [ ] **Step 2: Run feature tests**
  ```bash
  docker compose run --rm app php artisan test tests/Feature/Modules/Workflow/WorkflowApiTest.php --compact
  ```
  Expected: PASS.

- [ ] **Step 3: Write README**

  Document aggregates, status machine, endpoints, permissions, test commands.

- [ ] **Step 4: Commit**
  ```bash
  git add src/backend/tests/Feature/Modules/Workflow/ src/backend/app/Modules/Workflow/README.md
  git commit -m "test(workflow): add API coverage and docs"
  ```

---

### Task 9: Final verification

- [ ] **Step 1: Run targeted tests**
  ```bash
  docker compose run --rm app php artisan test tests/Unit/Modules/Workflow tests/Feature/Modules/Workflow --compact
  ```
  Expected: PASS.

- [ ] **Step 2: Run full backend suite**
  ```bash
  docker compose run --rm app php artisan test --compact
  ```
  Expected: PASS; report count.

- [ ] **Step 3: Run spec checklist**

  Verify all 15 ACs from spec section 12.

- [ ] **Step 4: Push and PR**
  ```bash
  git push -u origin feature/workflow
  ```
  PR: `feat(workflow): add Phase 2 Workflow BC`
