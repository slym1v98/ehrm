<?php

namespace Tests\Unit\Modules\Notification\Domain;

use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplate;
use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplateId;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessage;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessageId;
use App\Modules\Notification\Domain\Aggregates\NotificationOutbox\NotificationOutbox;
use App\Modules\Notification\Domain\Aggregates\NotificationOutbox\NotificationOutboxId;
use App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreference;
use App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreferenceId;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Domain\ValueObjects\NotificationPriority;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class AggregatesTest extends TestCase
{
    private MessageTemplate $template;

    protected function setUp(): void
    {
        $this->template = MessageTemplate::create(
            MessageTemplateId::generate(),
            'test.code',
            'Test',
            Channel::InApp,
            'Hello {{name}}',
            'Body {{name}} {{action}}',
            ['name', 'action'],
        );
    }

    public function test_template_render_substitutes(): void
    {
        $rendered = $this->template->render(['name' => 'Alice', 'action' => 'approved']);

        $this->assertSame('Hello Alice', $rendered['subject']);
        $this->assertSame('Body Alice approved', $rendered['body']);
    }

    public function test_template_activate_deactivate(): void
    {
        $this->assertTrue($this->template->isActive());
        $this->template->deactivate();
        $this->assertFalse($this->template->isActive());
        $this->template->activate();
        $this->assertTrue($this->template->isActive());
    }

    public function test_message_mark_read_only_once(): void
    {
        $msg = NotificationMessage::create(
            NotificationMessageId::generate(),
            $this->template,
            'user-1',
            null,
            ['subject' => 'Test', 'body' => 'Body'],
            [],
        );

        $msg->markRead(CarbonImmutable::now());
        $this->assertNotNull($msg->getReadAt());

        $this->expectException(\InvalidArgumentException::class);
        $msg->markRead(CarbonImmutable::now());
    }

    public function test_message_mark_sent_only_once(): void
    {
        $msg = NotificationMessage::create(
            NotificationMessageId::generate(),
            $this->template,
            'user-1',
            null,
            ['subject' => 'Test', 'body' => 'Body'],
            [],
        );

        $msg->markSent(CarbonImmutable::now());
        $this->assertNotNull($msg->getSentAt());

        $this->expectException(\InvalidArgumentException::class);
        $msg->markSent(CarbonImmutable::now());
    }

    public function test_outbox_fail_increments_attempts(): void
    {
        $msg = NotificationMessage::create(
            NotificationMessageId::generate(),
            $this->template,
            'user-1',
            null,
            ['subject' => 'Test', 'body' => 'Body'],
            [],
        );
        $outbox = NotificationOutbox::create(NotificationOutboxId::generate(), $msg);

        $this->assertSame(0, $outbox->getAttempts());
        $outbox->lock('w1', CarbonImmutable::now());
        $outbox->fail('Connection error');

        $this->assertSame(1, $outbox->getAttempts());
        $this->assertFalse($outbox->canRetry() === false); // 1 < 3
    }

    public function test_outbox_max_attempts_terminal(): void
    {
        $msg = NotificationMessage::create(
            NotificationMessageId::generate(),
            $this->template,
            'user-1',
            null,
            ['subject' => 'Test', 'body' => 'Body'],
            [],
        );
        $outbox = NotificationOutbox::create(NotificationOutboxId::generate(), $msg);

        for ($i = 0; $i < 3; $i++) {
            $outbox->lock('w1', CarbonImmutable::now());
            $outbox->fail('err');
        }

        $this->assertSame(3, $outbox->getAttempts());
        $this->assertFalse($outbox->canRetry());
    }

    public function test_preference_match_by_channel_and_template(): void
    {
        $pref = UserNotificationPreference::set(
            UserNotificationPreferenceId::generate(),
            'user-1',
            Channel::Email,
            'leave.request.submitted',
        );

        $this->assertTrue($pref->matches(Channel::Email, 'leave.request.submitted'));
        $this->assertFalse($pref->matches(Channel::InApp, 'leave.request.submitted'));
        $this->assertFalse($pref->matches(Channel::Email, 'leave.request.approved'));
    }

    public function test_preference_null_template_matches_any(): void
    {
        $pref = UserNotificationPreference::set(
            UserNotificationPreferenceId::generate(),
            'user-1',
            Channel::InApp,
            null, // any template
            false,
        );

        $this->assertTrue($pref->matches(Channel::InApp, 'anything'));
    }
}
