# Notification Module

Phase 2 notification fan-out module.

## Behavior

- Producers call `NotificationPublisher::send()` with a template code, recipient user, channel, payload, and priority.
- `SendNotificationHandler` writes `notification_messages` and `notification_outbox`.
- `notifications:process-outbox` sends due outbox rows via channel adapters.
- Default adapters (`in_app`, `email`, `sms`) log only. No external network calls.
- Missing preference means allowed. Disabled preference blocks non-high-priority messages. High priority bypasses preferences.

## Worker

```bash
php artisan notifications:process-outbox --limit=50
```

## API

- `GET /api/v1/notifications`
- `GET /api/v1/notifications/unread-count`
- `PATCH /api/v1/notifications/{id}/read`
- `PATCH /api/v1/notifications/read-all`
- `GET /api/v1/notification-preferences`
- `PUT /api/v1/notification-preferences`
- `GET /api/v1/notification-templates`
- `POST /api/v1/notification-templates`
- `PATCH /api/v1/notification-templates/{id}`
- `POST /api/v1/notification-templates/{id}/activate`
- `POST /api/v1/notification-templates/{id}/deactivate`

## Permissions

- `notification.view-own`
- `notification.mark-read-own`
- `notification.preference.manage-own`
- `notification.template.view`
- `notification.template.manage`
- `notification.outbox.process`

## Producer example

```php
$this->notificationPublisher->send(
    templateCode: 'leave.request.approved',
    recipientUserId: $employeeUserId,
    channel: Channel::InApp,
    payload: ['leave_type' => 'Annual', 'start_date' => '2026-07-10'],
);
```
