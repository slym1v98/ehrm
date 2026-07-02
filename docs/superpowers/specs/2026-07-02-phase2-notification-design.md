# Phase 2 Notification BC Design

Version: 0.1
Date: 2026-07-02
Status: Design approved (brainstorming)

## 1. Scope

Build Notification module (`app/Modules/Notification/`) as the sixth Phase 2 module. Covers reusable multi-channel notification fan-out via DB outbox pattern, message templates, per-user channel preferences, and bounded retry.

**In scope:** `MessageTemplate` CRUD with channel-specific subj/body placeholders, `NotificationMessage` persist/read/mark-read, `UserNotificationPreference` opt-in/out (high-priority bypasses), `NotificationOutbox` worker (pending → processing → sent/failed, exponential backoff), channel adapters (in_app/email/sms) behind a contract, `NotificationPublisher` application service for producer modules, permission integration with Identity module, full test suite (domain unit + application + feature).

**Out of scope:** Refactoring existing module domain events to use Laravel Event system (producers call `NotificationPublisher` directly), notification delivery SLA/deadlines, delegated approval notification, SMS/email provider credential management (config-bound, log default), push notification (mobile, browser), scheduled/digest notifications, WebSocket/realtime broadcast, notification grouping/threading.

## 2. Architecture

Strict DDD tactical pattern with 3 layers, mirroring all existing Phase 2 modules.

```
Module/Notification/
  Domain/         — Pure PHP, no Laravel deps
  Application/    — Commands/Handlers + Queries
  Infrastructure/ — Eloquent, HTTP controllers, channel adapters, seeders, routes
```

**Dependency:** Domain ← Application ← Infrastructure.

**Key architectural decisions:**

- **DB outbox pattern (C from Phase 2 AC):** Each `SendNotification` command writes both `NotificationMessage` and `NotificationOutbox` in the same DB transaction. A background worker (CLI command or queued job) polls the outbox table, dispatches via channel adapters, and marks outbox as sent or failed. This gives at-least-once delivery guarantee without requiring a distributed transaction or event bus.
- **Direct service invocation, not event broadcasting:** Producers call `NotificationPublisher::send(template_code, recipient_user_id, payload)` from their application handlers. No refactoring of existing domain events (POPO pattern) or EventServiceProvider setup. Simpler surface, zero blast radius.
- **Channel adapter pattern:** `NotificationChannelInterface` with `send(template, message): void`. Each channel (in_app/email/sms) implements the interface. Default adapters log to Laravel `Log` channel. Replacing with real provider requires only writing a new adapter class and binding it in the service provider. No domain changes.
- **Preferences as allow-list default:** Absent preference record = allowed. Explicit `enabled: false` record = opt-out. High-priority messages skip preference check entirely.

## 3. Module Layout

```
app/Modules/Notification/
  Domain/
    Aggregates/
      MessageTemplate/
        MessageTemplate.php
        MessageTemplateId.php
      NotificationMessage/
        NotificationMessage.php
        NotificationMessageId.php
      UserNotificationPreference/
        UserNotificationPreference.php
        UserNotificationPreferenceId.php
      NotificationOutbox/
        NotificationOutbox.php
        NotificationOutboxId.php
    ValueObjects/
      Channel.php
      MessageStatus.php
      OutboxStatus.php
      NotificationPriority.php
    Services/
      ChannelDispatcher.php
      NotificationPublisher.php
    Events/
      NotificationQueued.php
      NotificationSent.php
      NotificationFailed.php
    Repositories/
      MessageTemplateRepositoryInterface.php
      NotificationMessageRepositoryInterface.php
      UserNotificationPreferenceRepositoryInterface.php
      NotificationOutboxRepositoryInterface.php
    Exceptions/
      MessageTemplateNotFoundException.php
      ChannelDeliveryFailedException.php
  Application/
    Commands/
      SendNotificationCommand.php
      ProcessOutboxCommand.php
      MarkMessageReadCommand.php
      MarkAllReadCommand.php
    CommandHandlers/
      SendNotificationHandler.php
      ProcessOutboxHandler.php
      MarkMessageReadHandler.php
      MarkAllReadHandler.php
    Queries/
      ListUserNotificationsQuery.php
      GetUnreadCountQuery.php
      ListTemplatesQuery.php
    QueryHandlers/
      ListUserNotificationsHandler.php
      GetUnreadCountHandler.php
      ListTemplatesHandler.php
  Infrastructure/
    Persistence/
      Eloquent/
        MessageTemplateModel.php
        NotificationMessageModel.php
        UserNotificationPreferenceModel.php
        NotificationOutboxModel.php
      Repositories/
        EloquentMessageTemplateRepository.php
        EloquentNotificationMessageRepository.php
        EloquentUserNotificationPreferenceRepository.php
        EloquentNotificationOutboxRepository.php
    Http/
      Controllers/
        NotificationController.php
        NotificationPreferenceController.php
        MessageTemplateController.php
      Requests/
        CreateMessageTemplateRequest.php
        UpdateMessageTemplateRequest.php
        UpdateNotificationPreferenceRequest.php
      Resources/
        NotificationMessageResource.php
        MessageTemplateResource.php
        UserNotificationPreferenceResource.php
    Channels/
      InAppChannel.php
      EmailChannel.php
      SmsChannel.php
      Contracts/
        NotificationChannelInterface.php
    Providers/
      NotificationServiceProvider.php
    Seeders/
      NotificationPermissionSeeder.php
  Routes/api.php
```

## 4. Schema

### `notification_templates` (migration `2026_07_02_110001`)

| Column | Type | Notes |
|---|---|---|
| id | uuid | PK |
| code | varchar(100) | unique, e.g. `leave.request.submitted`, `attendance.adjustment.approved` |
| name | varchar(255) | Human-friendly label |
| channel | varchar(20) | in_app / email / sms |
| subject | varchar(500) | Rendered text with `{{var}}` placeholders |
| body | text | Rendered body with `{{var}}` placeholders |
| variables | jsonb | Schema hint: `["employee_name", "leave_date"]` |
| is_active | boolean | Default true |
| timestamps | | created_at, updated_at |

### `notification_messages` (migration `2026_07_02_110002`)

| Column | Type | Notes |
|---|---|---|
| id | uuid | PK |
| template_code | varchar(100) | Snapshot, not FK — template may change |
| channel | varchar(20) | in_app / email / sms |
| recipient_user_id | uuid | Indexed, FK to users |
| recipient_address | varchar(255) | nullable — email or phone snapshot at send time |
| subject_rendered | varchar(500) | |
| body_rendered | text | |
| payload | jsonb | Original notification payload for re-rendering |
| status | varchar(20) | pending / queued / sent / failed, indexed |
| priority | varchar(20) | low / normal / high |
| error | text | nullable |
| read_at | timestamptz | nullable |
| sent_at | timestamptz | nullable |
| created_at | timestamptz | |
| updated_at | timestamptz | |

Indexes: `(recipient_user_id)`, `(status, priority)`, `(created_at)`.

### `user_notification_preferences` (migration `2026_07_02_110003`)

| Column | Type | Notes |
|---|---|---|
| id | uuid | PK |
| user_id | uuid | FK to users, indexed |
| channel | varchar(20) | |
| template_code | varchar(100) | nullable — null = applies to all templates for this channel |
| enabled | boolean | |
| timestamps | | created_at, updated_at |

Unique constraint: `(user_id, channel, template_code)`. Nulls excluded via partial unique index.

### `notification_outbox` (migration `2026_07_02_110004`)

| Column | Type | Notes |
|---|---|---|
| id | uuid | PK |
| notification_message_id | uuid | FK to notification_messages |
| channel | varchar(20) | Denormalized for adapter routing |
| status | varchar(20) | pending / processing / sent / failed, indexed |
| attempts | integer | Default 0 |
| max_attempts | integer | Default 3 |
| available_at | timestamptz | Indexed — controls retry schedule |
| locked_at | timestamptz | nullable, worker concurrency guard |
| locked_by | varchar(100) | nullable, worker identifier |
| last_error | text | nullable |
| created_at | timestamptz | |
| updated_at | timestamptz | |

Indexes: `(status, attempts, available_at)` for worker query.

## 5. Domain Model

### 5.1 MessageTemplate

```
MessageTemplate {
  id: MessageTemplateId (UUID VO)
  code: string (unique)
  name: string
  channel: Channel (VO)
  subject: string
  body: string
  variables: array
  active: bool

  static create(code, name, channel, subject, body, variables, active): self
  update(name, subject, body, variables, active): void
  activate(): void
  deactivate(): void

  render(params: array): RenderedMessage { subject: string, body: string }

  Invariants:
  - Code is unique and immutable after creation.
  - Channel is immutable after creation.
  - Variable names in template must match keys documented in the code's convention.
}
```

### 5.2 NotificationMessage

```
NotificationMessage {
  id: NotificationMessageId (UUID VO)
  templateCode: string
  channel: Channel
  recipientUserId: string
  recipientAddress: ?string
  subjectRendered: string
  bodyRendered: string
  payload: array
  status: MessageStatus
  priority: NotificationPriority
  error: ?string
  readAt: ?CarbonImmutable
  sentAt: ?CarbonImmutable

  static create(template, recipientUserId, recipientAddress, payload, priority): self
  markRead(at): void
  markSent(at): void
  markFailed(error): void

  Invariants:
  - readAt can only be set once and only for in_app channel messages.
  - sentAt can only be set once.
  - Cannot mark read if already marked.
}
```

### 5.3 UserNotificationPreference

```
UserNotificationPreference {
  id: UserNotificationPreferenceId (UUID VO)
  userId: string
  channel: Channel
  templateCode: ?string
  enabled: bool

  static set(userId, channel, templateCode, enabled): self
  toggle(): void

  matches(channel, templateCode): bool

  Invariants:
  - Absent preference = allowed (default allow).
  - high priority notifications ignore preference check.
}
```

### 5.4 NotificationOutbox

```
NotificationOutbox {
  id: NotificationOutboxId (UUID VO)
  notificationMessageId: NotificationMessageId
  channel: Channel
  status: OutboxStatus
  attempts: int
  maxAttempts: int
  availableAt: CarbonImmutable
  lockedAt: ?CarbonImmutable
  lockedBy: ?string
  lastError: ?string

  static create(fromMessage, channel): self
  lock(workerId, at): void
  succeed(at): void
  fail(error, backoffSeconds): void

  canRetry(): bool
  nextAvailableAt(): CarbonImmutable

  Invariants:
  - Failed outbox with attempts >= maxAttempts is terminal (status stays failed).
  - Locked outbox can only be processed by the locking worker.
  - Exponential backoff: backoffBase * 2^attempts seconds.
}
```

## 6. Application Layer

### 6.1 SendNotificationHandler

```
handle(SendNotificationCommand):
  1. Load MessageTemplate by code. Not found → throw MessageTemplateNotFoundException.
  2. Check UserNotificationPreference for (recipientUserId, channel, template.code).
     If preference exists and !enabled → skip (return quietly).
  3. Verify template.channel matches command.channel. Mismatch → skip.
  4. Render subject/body via template.render(payload).
  5. Resolve recipient_address (email/phone) from payload or user service.
     Current Phase 2: address is passed in payload or null.
  6. Create NotificationMessage with status=pending.
  7. Create NotificationOutbox with status=pending, available_at=now.
  8. Persist both in transaction.
  9. Dispatch NotificationQueued event.
```

### 6.2 ProcessOutboxHandler

```
handle(ProcessOutboxCommand):
  1. Query outbox where status IN (pending,failed) AND attempts < maxAttempts
     AND available_at <= now(). ORDER BY priority DESC, created_at ASC.
  2. FOR UPDATE SKIP LOCKED LIMIT N (configurable, default 50).
  3. For each outbox row:
     a. Mark outbox as processing, lock with worker ID.
     b. Load corresponding NotificationMessage.
     c. Resolve channel adapter via ChannelDispatcher.
     d. Call adapter.send(template, message).
     e. On success: outbox.succeed(now()), message.markSent(now()).
        Dispatch NotificationSent event.
     f. On failure: outbox.fail(error, backoffSeconds=60*2^attempts).
        message.markFailed(error). Dispatch NotificationFailed event.
     g. Persist updates.
```

### 6.3 MarkMessageReadHandler

```
1. Load NotificationMessage by id. Not found → 404.
2. Verify requesting user matches recipientUserId. Mismatch → 403.
3. Verify channel is in_app.
4. message.markRead(now()). Persist.
```

### 6.4 Queries

- `ListUserNotificationsQuery` → paginated by `recipient_user_id`, filtered by status, ordered by `created_at DESC`. Returns list with unread count.
- `GetUnreadCountQuery` → simple count of user's messages where `read_at IS NULL` and `channel = in_app`.
- `ListTemplatesQuery` → admin-only, paginated list of all templates, filterable by `channel`, `is_active`.

## 7. Scheduled/Queue Worker

The outbox processor runs as a Laravel scheduled command or a queued job with a delay loop:

```bash
# Kernel schedule (preferred for now, no supervisor daemon needed)
$schedule->command('notifications:process-outbox')->everyMinute();
```

Command: `php artisan notifications:process-outbox`.
Internally dispatches `ProcessOutboxCommand` to the command bus.

For high-volume scenarios, the command can be replaced with a Laravel job that dispatches itself if outbox remains non-empty. Default: simple cron-compatible command.

## 8. Channel Adapters

All adapters implement `NotificationChannelInterface`:

```php
interface NotificationChannelInterface {
    public function send(MessageTemplate $template, NotificationMessage $message): void;
}
```

**InAppChannel** — no-op on send (message already persisted in `notification_messages` with status). Only logs. The "send" here means "make available for in-app reading", which is already true by virtue of the row existing.

**EmailChannel** (default: `LogEmailChannel`) — logs subject, body, and recipient to Laravel `Log::info('email', ...)`. Production: swap binding to `MailEmailChannel` using Laravel Mail facade.

**SmsChannel** (default: `LogSmsChannel`) — logs body and recipient to Laravel `Log::info('sms', ...)`. Production: swap binding to `TwilioSmsChannel` or similar.

Adapters registered via `NotificationServiceProvider`:

```php
$this->app->bind(NotificationChannelInterface::class . ':in_app', InAppChannel::class);
$this->app->bind(NotificationChannelInterface::class . ':email', LogEmailChannel::class);
$this->app->bind(NotificationChannelInterface::class . ':sms', LogSmsChannel::class);
```

## 9. API Endpoints

### Employee/User Notification Endpoints

All prefixed with `/api/v1/notifications`, Sanctum auth.

| Method | Path | Handler | Permission |
|---|---|---|---|
| GET | `/notifications` | ListUserNotifications | notification.view-own (implicit: own data only) |
| GET | `/notifications/unread-count` | GetUnreadCount | notification.view-own |
| PATCH | `/notifications/{id}/read` | MarkMessageRead | notification.mark-read-own |
| PATCH | `/notifications/read-all` | MarkAllRead | notification.mark-read-own |
| GET | `/notification-preferences` | ListPreferences | notification.preference.manage-own |
| PUT | `/notification-preferences` | UpdatePreferences | notification.preference.manage-own |

### Admin Template Endpoints

| Method | Path | Permission |
|---|---|---|
| GET | `/notification-templates` | notification.template.view |
| POST | `/notification-templates` | notification.template.manage |
| PATCH | `/notification-templates/{id}` | notification.template.manage |
| PATCH | `/notification-templates/{id}/activate` | notification.template.manage |
| PATCH | `/notification-templates/{id}/deactivate` | notification.template.manage |

### Ops Endpoints

| Method | Path | Permission |
|---|---|---|
| POST | `/notification-outbox/process` | notification.outbox.process |

## 10. Permissions

Seeded in `NotificationPermissionSeeder`:

| Code | Group | Description |
|---|---|---|
| notification.view-own | Notification | View own notifications |
| notification.mark-read-own | Notification | Mark own notifications read |
| notification.preference.manage-own | Notification | Manage own notification preferences |
| notification.template.view | Notification | View notification templates |
| notification.template.manage | Notification | Create/update notification templates |
| notification.outbox.process | Notification | Manually trigger outbox processing |

Default role assignments (via existing `PermissionSeeder` convention):
- Admin: all
- HR Manager: all except outbox.process
- HR Staff: notification.view-own, mark-read-own, preference.manage-own
- Department Manager: notification.view-own, mark-read-own, preference.manage-own
- Employee: notification.view-own, mark-read-own, preference.manage-own

## 11. Error Handling

| Scenario | Behavior |
|---|---|
| Template not found during send | `MessageTemplateNotFoundException` → caller (module handler) catches and logs warning. Notification skipped. Not a business error. |
| Preference opt-out | Check → return silently. No error logged. |
| Channel adapter fails | `ChannelDeliveryFailedException` → ProcessOutboxHandler sets outbox to failed, increments attempts, schedules retry. NotificationMessage error field written. |
| Max retries exceeded | Outbox stays in `failed` state. Admin can manually retry or trigger outbox process command. Worker never retries again. |
| Outbox worker concurrency | `FOR UPDATE SKIP LOCKED` on PostgreSQL. Each outbox row locked by one worker. Deadlock risk minimal. |
| NotificationMessage not found for outbox row | Should not happen (FK constraint). Log warning, mark outbox as failed terminal. |

## 12. Producer Integration

Producer modules add one dependency injection call. No events, no listeners, no config changes:

```php
// In LeaveRequestApprovedHandler:
$this->notificationPublisher->send(
    template_code: 'leave.request.approved',
    recipient_user_id: $command->employeeId,
    channel: 'in_app',
    payload: [
        'employee_name' => $employee->name,
        'leave_type' => $leaveType->name,
        'start_date' => $startDate,
        'approved_by' => $approverName,
    ],
);
```

Initial template codes to seed (subject to expansion):
- `leave.request.submitted` (in_app)
- `leave.request.approved` (in_app)
- `leave.request.rejected` (in_app)
- `attendance.adjustment.approved` (in_app)
- `attendance.adjustment.rejected` (in_app)
- `shift.assigned` (in_app)
- `shift.assignment.ended` (in_app)
- `payroll.payslip.available` (in_app + email)
- `workflow.step.assigned` (in_app)
- `workflow.approved` (in_app)
- `workflow.rejected` (in_app)
- `workflow.returned` (in_app)
- `security.unauthorized.access` (in_app, high priority)

## 13. Testing

### Domain Unit Tests (`tests/Unit/Modules/Notification/Domain/`)
- MessageTemplate: create, update, activate/deactivate, render with placeholder substitution
- NotificationMessage: create, markRead, markSent, markFailed, state transition guards
- UserNotificationPreference: set, toggle, matches, default-allow invariant
- NotificationOutbox: create, lock, succeed, fail, canRetry, exponential backoff, max attempts terminal

### Application Unit Tests (`tests/Unit/Modules/Notification/Application/`)
- SendNotificationHandler: successful send, template not found, preference opt-out skip, channel mismatch skip, high-priority bypasses preference
- ProcessOutboxHandler: dispatches channel adapter, marks sent on success, marks failed on error, respects maxAttempts, respects SKIP LOCKED
- MarkMessageReadHandler: own message succeeds, other user's message forbidden, non-in_app channel rejected

### Feature Tests (`tests/Feature/Modules/Notification/`)
- Unauthenticated access returns 401
- User can list own notifications (cannot see others')
- User can mark own notification read
- User cannot mark other user's notification read
- Admin can CRUD templates
- Non-admin cannot create/update templates
- Opt-in/opt-out preferences via PUT endpoint
- Outbox process endpoint requires permission

## 14. Acceptance Criteria

1. ✅ Notification BC module follows Phase 2 DDD layout conventions.
2. ✅ SendNotification writes message + outbox in single transaction.
3. ✅ ProcessOutbox worker dispatches all 3 channels (in_app/email/sms) via adapter contract.
4. ✅ Channel adapters default to log driver; production swap requires only a ServiceProvider change.
5. ✅ UserNotificationPreference enables/disables per (user, channel, template_code).
6. ✅ High-priority messages bypass preference check.
7. ✅ Outbox respects maxAttempts (3) with exponential backoff.
8. ✅ Producer modules call an application service; no event refactoring required.
9. ✅ 12+ seed template codes for Leave, Attendance, Shift, Payroll, Workflow events.
10. ✅ Full permission set seeded with role defaults.
11. ✅ Domain, application, and feature tests exist with auth-boundary coverage.
