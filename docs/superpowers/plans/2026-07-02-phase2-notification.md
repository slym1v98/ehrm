# Phase 2 Notification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Phase 2 Notification module with template CRUD, user inbox/preferences, multi-channel log adapters, and DB outbox processing.

**Architecture:** Strict DDD structure under `src/backend/app/Modules/Notification`. Producers call `NotificationPublisher`; no existing module event refactor. `SendNotificationHandler` persists `NotificationMessage` + `NotificationOutbox` in one DB transaction; `ProcessOutboxHandler` sends via channel adapters and applies bounded retry.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL 16 JSONB/UUIDs, Sanctum, Eloquent repositories, PHPUnit.

---

## File Map

- `src/backend/app/Modules/Notification/Domain/**`: aggregates, value objects, events, exceptions, repository contracts, publisher/dispatcher services.
- `src/backend/app/Modules/Notification/Application/**`: commands, handlers, queries, query handlers.
- `src/backend/app/Modules/Notification/Infrastructure/**`: Eloquent models/repositories, HTTP controllers/requests/resources, channel adapters, provider, seeder.
- `src/backend/app/Modules/Notification/Routes/api.php`: module routes under `/api/v1`.
- `src/backend/database/migrations/2026_07_02_11000*_create_notification_*.php`: Notification schema.
- `src/backend/app/Providers/AppServiceProvider.php`: bind Notification repositories/services if existing provider pattern does not auto-register module provider.
- `src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php`: add `notification.*` permissions.
- `src/backend/app/Modules/Identity/Infrastructure/Seeders/RoleSeeder.php`: grant defaults to Admin/HR roles and own-notification permissions to employee roles.
- `src/backend/routes/api.php`: require Notification route file.
- `src/backend/tests/Unit/Modules/Notification/**`: domain/application tests.
- `src/backend/tests/Feature/Modules/Notification/**`: HTTP + authz tests.
- `src/backend/app/Modules/Notification/README.md`: module behavior, endpoints, permissions, worker command.

---

### Task 1: Database schema

**Files:**
- Create: `src/backend/database/migrations/2026_07_02_110001_create_notification_templates_table.php`
- Create: `src/backend/database/migrations/2026_07_02_110002_create_notification_messages_table.php`
- Create: `src/backend/database/migrations/2026_07_02_110003_create_user_notification_preferences_table.php`
- Create: `src/backend/database/migrations/2026_07_02_110004_create_notification_outbox_table.php`

- [ ] **Step 1: Create templates migration**

Create `notification_templates`: UUID `id`, unique `code`, `name`, `channel`, nullable `subject`, `body`, JSONB `variables` default `[]`, boolean `is_active` default true, timestamps; indexes on `channel`, `is_active`.

Run: `docker compose run --rm app php artisan migrate:fresh --seed`
Expected: PASS; `notification_templates` exists.

- [ ] **Step 2: Create messages migration**

Create `notification_messages`: UUID `id`, `template_code`, `channel`, UUID `recipient_user_id`, nullable `recipient_address`, nullable `subject_rendered`, `body_rendered`, JSONB `payload` default `{}`, `status`, `priority`, nullable `error`, nullable `read_at`, nullable `sent_at`, timestamps; indexes on `recipient_user_id`, `(status, priority)`, `created_at`.

Run: `docker compose run --rm app php artisan migrate:fresh --seed`
Expected: PASS; message indexes exist.

- [ ] **Step 3: Create preferences migration**

Create `user_notification_preferences`: UUID `id`, UUID `user_id`, `channel`, nullable `template_code`, boolean `enabled`, timestamps; index `(user_id, channel)`; unique `(user_id, channel, template_code)`.

Run: `docker compose run --rm app php artisan migrate:fresh --seed`
Expected: PASS; preference unique guard exists.

- [ ] **Step 4: Create outbox migration**

Create `notification_outbox`: UUID `id`, FK `notification_message_id` to `notification_messages(id)` cascade delete, `channel`, `status`, `attempts` default 0, `max_attempts` default 3, `available_at`, nullable `locked_at`, nullable `locked_by`, nullable `last_error`, timestamps; index `(status, attempts, available_at)`.

Run: `docker compose run --rm app php artisan migrate:fresh --seed`
Expected: PASS; outbox worker index exists.

- [ ] **Step 5: Commit schema**

```bash
git add src/backend/database/migrations/2026_07_02_110001_create_notification_templates_table.php \
  src/backend/database/migrations/2026_07_02_110002_create_notification_messages_table.php \
  src/backend/database/migrations/2026_07_02_110003_create_user_notification_preferences_table.php \
  src/backend/database/migrations/2026_07_02_110004_create_notification_outbox_table.php
git commit -m "feat(notification): add schema"
```

---

### Task 2: Eloquent models

**Files:**
- Create: `src/backend/app/Modules/Notification/Infrastructure/Persistence/Eloquent/MessageTemplateModel.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Persistence/Eloquent/NotificationMessageModel.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Persistence/Eloquent/UserNotificationPreferenceModel.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Persistence/Eloquent/NotificationOutboxModel.php`

- [ ] **Step 1: Add MessageTemplateModel**

Use table `notification_templates`, string UUID key, non-incrementing, fillable all columns except timestamps, casts `variables` array and `is_active` boolean.

- [ ] **Step 2: Add NotificationMessageModel**

Use table `notification_messages`, string UUID key, casts `payload` array, `read_at` datetime, `sent_at` datetime.

- [ ] **Step 3: Add UserNotificationPreferenceModel**

Use table `user_notification_preferences`, string UUID key, casts `enabled` boolean.

- [ ] **Step 4: Add NotificationOutboxModel**

Use table `notification_outbox`, string UUID key, casts `available_at`, `locked_at` datetime, `attempts`/`max_attempts` integer.

- [ ] **Step 5: Smoke test autoload**

Run: `docker compose run --rm app php artisan test --filter=ExampleTest --compact`
Expected: PASS or no model autoload errors.

- [ ] **Step 6: Commit models**

```bash
git add src/backend/app/Modules/Notification/Infrastructure/Persistence/Eloquent
git commit -m "feat(notification): add eloquent models"
```

---

### Task 3: Domain value objects and tests

**Files:**
- Create: `src/backend/app/Modules/Notification/Domain/Aggregates/MessageTemplate/MessageTemplateId.php`
- Create: `src/backend/app/Modules/Notification/Domain/Aggregates/NotificationMessage/NotificationMessageId.php`
- Create: `src/backend/app/Modules/Notification/Domain/Aggregates/UserNotificationPreference/UserNotificationPreferenceId.php`
- Create: `src/backend/app/Modules/Notification/Domain/Aggregates/NotificationOutbox/NotificationOutboxId.php`
- Create: `src/backend/app/Modules/Notification/Domain/ValueObjects/Channel.php`
- Create: `src/backend/app/Modules/Notification/Domain/ValueObjects/MessageStatus.php`
- Create: `src/backend/app/Modules/Notification/Domain/ValueObjects/OutboxStatus.php`
- Create: `src/backend/app/Modules/Notification/Domain/ValueObjects/NotificationPriority.php`
- Test: `src/backend/tests/Unit/Modules/Notification/Domain/ValueObjectsTest.php`

- [ ] **Step 1: Write failing VO tests**

Cover UUID round-trip, enum values, invalid enum values, and priority ordering (`high > normal > low`).

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Notification/Domain/ValueObjectsTest.php --compact`
Expected: FAIL because classes missing.

- [ ] **Step 2: Implement IDs and enums**

Implement UUID wrapper IDs with `new()`, `fromString()`, `toString()`. Implement enums as PHP backed enums with helper `fromString(string): self` where existing modules use that pattern. `NotificationPriority::weight()` returns `3/2/1`.

- [ ] **Step 3: Run VO tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Notification/Domain/ValueObjectsTest.php --compact`
Expected: PASS.

- [ ] **Step 4: Commit VOs**

```bash
git add src/backend/app/Modules/Notification/Domain/Aggregates/*/*Id.php \
  src/backend/app/Modules/Notification/Domain/ValueObjects \
  src/backend/tests/Unit/Modules/Notification/Domain/ValueObjectsTest.php
git commit -m "feat(notification): add domain value objects"
```

---

### Task 4: Domain aggregates, events, exceptions

**Files:**
- Create: `src/backend/app/Modules/Notification/Domain/Aggregates/MessageTemplate/MessageTemplate.php`
- Create: `src/backend/app/Modules/Notification/Domain/Aggregates/NotificationMessage/NotificationMessage.php`
- Create: `src/backend/app/Modules/Notification/Domain/Aggregates/UserNotificationPreference/UserNotificationPreference.php`
- Create: `src/backend/app/Modules/Notification/Domain/Aggregates/NotificationOutbox/NotificationOutbox.php`
- Create: `src/backend/app/Modules/Notification/Domain/Events/NotificationQueued.php`
- Create: `src/backend/app/Modules/Notification/Domain/Events/NotificationSent.php`
- Create: `src/backend/app/Modules/Notification/Domain/Events/NotificationFailed.php`
- Create: `src/backend/app/Modules/Notification/Domain/Exceptions/MessageTemplateNotFoundException.php`
- Create: `src/backend/app/Modules/Notification/Domain/Exceptions/ChannelDeliveryFailedException.php`
- Create: `src/backend/app/Modules/Notification/Domain/Exceptions/NotificationMessageNotFoundException.php`
- Test: `src/backend/tests/Unit/Modules/Notification/Domain/AggregatesTest.php`

- [ ] **Step 1: Write failing aggregate tests**

Cover template render placeholder substitution, template activate/deactivate, message markRead only once, message markSent only once, outbox fail increments attempts and sets backoff, outbox max attempts terminal, preference match by channel/template/null-template.

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Notification/Domain/AggregatesTest.php --compact`
Expected: FAIL because aggregates missing.

- [ ] **Step 2: Implement MessageTemplate**

Add `create`, `update`, `activate`, `deactivate`, `render(array $params): array`. Placeholder rendering uses exact `str_replace('{{'.$key.'}}', (string) $value, $text)` for each param. No expression language.

- [ ] **Step 3: Implement NotificationMessage**

Add `create`, `markRead`, `markSent`, `markFailed`. Guard repeated `markRead` and repeated `markSent` with `InvalidArgumentException` to match simple existing modules.

- [ ] **Step 4: Implement UserNotificationPreference**

Add `set`, `toggle`, `matches(Channel $channel, string $templateCode): bool`. Null template matches any template for that channel.

- [ ] **Step 5: Implement NotificationOutbox**

Add `create`, `lock`, `succeed`, `fail`, `canRetry`, `nextBackoffSeconds`. Backoff rule: attempts after increment `1 => 60`, `2 => 300`, `3 => 1800`.

- [ ] **Step 6: Add POPO events and exceptions**

Events mirror existing modules: constructor with `public readonly array $payload`. Exceptions extend shared `AppException` if adjacent modules do; otherwise extend `RuntimeException`.

- [ ] **Step 7: Run aggregate tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Notification/Domain/AggregatesTest.php --compact`
Expected: PASS.

- [ ] **Step 8: Commit domain**

```bash
git add src/backend/app/Modules/Notification/Domain src/backend/tests/Unit/Modules/Notification/Domain/AggregatesTest.php
git commit -m "feat(notification): add domain layer"
```

---

### Task 5: Repositories

**Files:**
- Create: `src/backend/app/Modules/Notification/Domain/Repositories/MessageTemplateRepositoryInterface.php`
- Create: `src/backend/app/Modules/Notification/Domain/Repositories/NotificationMessageRepositoryInterface.php`
- Create: `src/backend/app/Modules/Notification/Domain/Repositories/UserNotificationPreferenceRepositoryInterface.php`
- Create: `src/backend/app/Modules/Notification/Domain/Repositories/NotificationOutboxRepositoryInterface.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Persistence/Repositories/EloquentMessageTemplateRepository.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Persistence/Repositories/EloquentNotificationMessageRepository.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Persistence/Repositories/EloquentUserNotificationPreferenceRepository.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Persistence/Repositories/EloquentNotificationOutboxRepository.php`

- [ ] **Step 1: Add repository interfaces**

Interfaces expose only needed methods:
`save`, `findById`, `findByCode`, `list`, `findPreference`, `findDueBatch`, `countUnread`, `listForUser`, `markAllRead`.

- [ ] **Step 2: Implement mappers**

Each Eloquent repository maps model ⇄ aggregate. Keep mapping private methods inside repository; no separate mapper classes.

- [ ] **Step 3: Implement due outbox query**

`findDueBatch(int $limit, string $workerId, DateTimeImmutable $now)` uses `whereIn(status, ['pending','failed'])`, `whereColumn('attempts','<','max_attempts')`, `where('available_at','<=',$now)`, orders by `available_at`, `created_at`, limits. Use DB transaction + lock when processing if repository patterns allow; otherwise lock in handler.

- [ ] **Step 4: Bind repositories**

Add repository bindings to `NotificationServiceProvider` if registered, else `AppServiceProvider`.

- [ ] **Step 5: Smoke test**

Run: `docker compose run --rm app php artisan test --filter=ExampleTest --compact`
Expected: PASS or no container binding/autoload errors.

- [ ] **Step 6: Commit repositories**

```bash
git add src/backend/app/Modules/Notification/Domain/Repositories \
  src/backend/app/Modules/Notification/Infrastructure/Persistence/Repositories \
  src/backend/app/Modules/Notification/Infrastructure/Providers/NotificationServiceProvider.php \
  src/backend/app/Providers/AppServiceProvider.php
git commit -m "feat(notification): add repositories"
```

---

### Task 6: Application handlers and channel adapters

**Files:**
- Create: `src/backend/app/Modules/Notification/Application/Commands/SendNotificationCommand.php`
- Create: `src/backend/app/Modules/Notification/Application/Commands/ProcessOutboxCommand.php`
- Create: `src/backend/app/Modules/Notification/Application/Commands/MarkMessageReadCommand.php`
- Create: `src/backend/app/Modules/Notification/Application/Commands/MarkAllReadCommand.php`
- Create: `src/backend/app/Modules/Notification/Application/CommandHandlers/SendNotificationHandler.php`
- Create: `src/backend/app/Modules/Notification/Application/CommandHandlers/ProcessOutboxHandler.php`
- Create: `src/backend/app/Modules/Notification/Application/CommandHandlers/MarkMessageReadHandler.php`
- Create: `src/backend/app/Modules/Notification/Application/CommandHandlers/MarkAllReadHandler.php`
- Create: `src/backend/app/Modules/Notification/Domain/Services/NotificationPublisher.php`
- Create: `src/backend/app/Modules/Notification/Domain/Services/ChannelDispatcher.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Channels/Contracts/NotificationChannelInterface.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Channels/InAppChannel.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Channels/EmailChannel.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Channels/SmsChannel.php`
- Test: `src/backend/tests/Unit/Modules/Notification/Application/NotificationHandlersTest.php`

- [ ] **Step 1: Write failing application tests**

Cover send success creates message+outbox, opt-out skips, high priority bypasses opt-out, process success marks sent, process failure schedules retry, mark read forbids other user, mark all read affects only current user.

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Notification/Application/NotificationHandlersTest.php --compact`
Expected: FAIL because handlers missing.

- [ ] **Step 2: Implement commands**

Use immutable readonly DTOs. `SendNotificationCommand` fields: `templateCode`, `recipientUserId`, `channel`, `payload`, `priority = 'normal'`, `recipientAddress = null`.

- [ ] **Step 3: Implement SendNotificationHandler**

Load template, skip inactive/mismatched channel, skip preference disabled unless high priority, render, persist message+outbox in transaction.

- [ ] **Step 4: Implement ProcessOutboxHandler**

Fetch due rows, lock/process sequentially, call dispatcher, on success mark message/outbox sent, on exception mark failed with backoff. Do not throw adapter exceptions to caller.

- [ ] **Step 5: Implement read handlers**

`MarkMessageReadHandler` checks owner and channel `in_app`. `MarkAllReadHandler` marks all unread `in_app` rows for the user.

- [ ] **Step 6: Implement channel adapters**

`InAppChannel` no-ops/logs. `EmailChannel` logs recipient/subject/body. `SmsChannel` logs recipient/body. No external network calls.

- [ ] **Step 7: Run application tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Notification/Application/NotificationHandlersTest.php --compact`
Expected: PASS.

- [ ] **Step 8: Commit application layer**

```bash
git add src/backend/app/Modules/Notification/Application \
  src/backend/app/Modules/Notification/Domain/Services \
  src/backend/app/Modules/Notification/Infrastructure/Channels \
  src/backend/tests/Unit/Modules/Notification/Application/NotificationHandlersTest.php
git commit -m "feat(notification): add application handlers and channels"
```

---

### Task 7: HTTP API, routes, artisan command

**Files:**
- Create: `src/backend/app/Modules/Notification/Infrastructure/Http/Controllers/NotificationController.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Http/Controllers/NotificationPreferenceController.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Http/Controllers/MessageTemplateController.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Http/Requests/CreateMessageTemplateRequest.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Http/Requests/UpdateMessageTemplateRequest.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Http/Requests/UpdateNotificationPreferenceRequest.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Http/Resources/NotificationMessageResource.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Http/Resources/MessageTemplateResource.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Http/Resources/UserNotificationPreferenceResource.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Console/ProcessNotificationOutboxCommand.php`
- Create: `src/backend/app/Modules/Notification/Routes/api.php`
- Modify: `src/backend/routes/api.php`

- [ ] **Step 1: Add API feature tests first**

Create `src/backend/tests/Feature/Modules/Notification/NotificationApiTest.php`. Cover 401 list, own list, unread count, mark own read, forbid mark other read, non-admin cannot create template, admin can create/update template, preference PUT, outbox process permission.

Run: `docker compose run --rm app php artisan test tests/Feature/Modules/Notification/NotificationApiTest.php --compact`
Expected: FAIL because routes/controllers missing.

- [ ] **Step 2: Implement controllers/resources/requests**

Match existing modules' response shape. Use authenticated user id for own endpoints; never accept `recipient_user_id` from own-list request.

- [ ] **Step 3: Implement routes**

Register under `/api/v1`: `/notifications`, `/notification-preferences`, `/notification-templates`, `/notification-outbox/process`. Use existing auth/permission middleware names from other module route files.

- [ ] **Step 4: Add artisan command**

Create command signature `notifications:process-outbox {--limit=50}`. It invokes `ProcessOutboxHandler` with worker id `cli-<hostname>-<pid>` and prints processed/sent/failed counts.

- [ ] **Step 5: Require module route file**

Modify `src/backend/routes/api.php` to require `app/Modules/Notification/Routes/api.php`, matching existing module loader style.

- [ ] **Step 6: Run API tests**

Run: `docker compose run --rm app php artisan test tests/Feature/Modules/Notification/NotificationApiTest.php --compact`
Expected: PASS.

- [ ] **Step 7: Commit HTTP layer**

```bash
git add src/backend/app/Modules/Notification/Infrastructure/Http \
  src/backend/app/Modules/Notification/Infrastructure/Console \
  src/backend/app/Modules/Notification/Routes/api.php \
  src/backend/routes/api.php \
  src/backend/tests/Feature/Modules/Notification/NotificationApiTest.php
git commit -m "feat(notification): add HTTP API and outbox command"
```

---

### Task 8: Permissions, seed templates, README

**Files:**
- Create: `src/backend/app/Modules/Notification/Infrastructure/Seeders/NotificationPermissionSeeder.php`
- Create: `src/backend/app/Modules/Notification/Infrastructure/Seeders/NotificationTemplateSeeder.php`
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php`
- Modify: `src/backend/app/Modules/Identity/Infrastructure/Seeders/RoleSeeder.php`
- Create: `src/backend/app/Modules/Notification/README.md`

- [ ] **Step 1: Add permission seeder**

Seed: `notification.view-own`, `notification.mark-read-own`, `notification.preference.manage-own`, `notification.template.view`, `notification.template.manage`, `notification.outbox.process`.

- [ ] **Step 2: Register permission seeder**

Call `NotificationPermissionSeeder` from the existing Identity permission seeder or database seeder pattern. Match current module seeder registration style exactly.

- [ ] **Step 3: Grant role defaults**

Admin gets all. HR Manager gets all except `notification.outbox.process`. HR Staff, Department Manager, Employee get own view/read/preferences.

- [ ] **Step 4: Add template seeder**

Seed 12 template codes from the spec: leave submitted/approved/rejected, attendance adjustment approved/rejected, shift assigned/ended, payroll payslip available (in_app + email), workflow assigned/approved/rejected/returned, security unauthorized access.

- [ ] **Step 5: Write README**

Document endpoints, permissions, outbox command, default log adapters, and producer usage example with `NotificationPublisher`.

- [ ] **Step 6: Verify seed**

Run: `docker compose run --rm app php artisan migrate:fresh --seed`
Expected: PASS; notification permissions and templates are seeded.

- [ ] **Step 7: Commit seeders/docs**

```bash
git add src/backend/app/Modules/Notification/Infrastructure/Seeders \
  src/backend/app/Modules/Identity/Infrastructure/Seeders/PermissionSeeder.php \
  src/backend/app/Modules/Identity/Infrastructure/Seeders/RoleSeeder.php \
  src/backend/app/Modules/Notification/README.md
git commit -m "feat(notification): seed permissions and templates"
```

---

### Task 9: Final verification and spec review

**Files:**
- Modify: `docs/superpowers/plans/2026-07-02-phase2-notification.md` (checkboxes only)

- [ ] **Step 1: Run targeted Notification tests**

Run: `docker compose run --rm app php artisan test tests/Unit/Modules/Notification tests/Feature/Modules/Notification --compact`
Expected: PASS; report count.

- [ ] **Step 2: Run full backend suite**

Run: `docker compose run --rm app php artisan test --compact`
Expected: PASS; report total count.

- [ ] **Step 3: Review spec acceptance criteria**

Open `docs/superpowers/specs/2026-07-02-phase2-notification-design.md` and verify AC1-11 against implementation. Record any gap in final handoff.

- [ ] **Step 4: Commit plan checkbox updates if used**

```bash
git add docs/superpowers/plans/2026-07-02-phase2-notification.md
git commit -m "docs(notification): update implementation checklist"
```

---

## Self-Review Checklist

- Spec coverage: tasks cover schema, domain, repos, handlers, channel adapters, HTTP, permissions, seed templates, outbox command, tests, README, full suite verification.
- Scope: no provider SDKs, no event bus refactor, no push/WebSocket/digests.
- Type consistency: template/message/preference/outbox names match spec; status/channel/priority values match spec; routes match spec.
- Test coverage: includes required auth boundaries and outbox retry behavior.
