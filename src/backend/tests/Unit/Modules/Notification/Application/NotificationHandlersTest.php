<?php

namespace Tests\Unit\Modules\Notification\Application;

use App\Modules\Notification\Application\CommandHandlers\MarkMessageReadHandler;
use App\Modules\Notification\Application\CommandHandlers\ProcessOutboxHandler;
use App\Modules\Notification\Application\CommandHandlers\SendNotificationHandler;
use App\Modules\Notification\Application\Commands\MarkMessageReadCommand;
use App\Modules\Notification\Application\Commands\ProcessOutboxCommand;
use App\Modules\Notification\Application\Commands\SendNotificationCommand;
use App\Modules\Notification\Application\Services\ChannelDispatcher;
use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplate;
use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplateId;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessage;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessageId;
use App\Modules\Notification\Domain\Aggregates\NotificationOutbox\NotificationOutbox;
use App\Modules\Notification\Domain\Aggregates\NotificationOutbox\NotificationOutboxId;
use App\Modules\Notification\Domain\Exceptions\MessageTemplateNotFoundException;
use App\Modules\Notification\Domain\Repositories\MessageTemplateRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\NotificationMessageRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\NotificationOutboxRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\UserNotificationPreferenceRepositoryInterface;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Domain\ValueObjects\NotificationPriority;
use App\Modules\Notification\Domain\ValueObjects\OutboxStatus;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class NotificationHandlersTest extends TestCase
{
    private function makeTemplate(): MessageTemplate
    {
        return MessageTemplate::create(
            MessageTemplateId::generate(),
            'leave.request.submitted',
            'Leave submitted',
            Channel::InApp,
            'Hello {{name}}',
            'Your request was submitted {{name}}',
            ['name'],
        );
    }

    private function makeSendHandler(
        ?MessageTemplate $template,
        ?MessageTemplateRepositoryInterface $templates = null,
        ?UserNotificationPreferenceRepositoryInterface $preferences = null,
    ): SendNotificationHandler {
        $templates ??= $this->createMock(MessageTemplateRepositoryInterface::class);
        $templates->method('findByCode')->willReturn($template);

        $preferences ??= $this->createMock(UserNotificationPreferenceRepositoryInterface::class);
        $preferences->method('findByUserAndChannel')->willReturn(null);

        $messages = $this->createMock(NotificationMessageRepositoryInterface::class);
        $outboxes = $this->createMock(NotificationOutboxRepositoryInterface::class);

        return new SendNotificationHandler($templates, $messages, $outboxes, $preferences);
    }

    public function test_send_creates_message_and_outbox(): void
    {
        $template = $this->makeTemplate();
        $templates = $this->createMock(MessageTemplateRepositoryInterface::class);
        $templates->method('findByCode')->willReturn($template);

        $preferences = $this->createMock(UserNotificationPreferenceRepositoryInterface::class);
        $preferences->method('findByUserAndChannel')->willReturn(null);

        $messages = $this->createMock(NotificationMessageRepositoryInterface::class);
        $messages->expects($this->once())->method('save');

        $outboxes = $this->createMock(NotificationOutboxRepositoryInterface::class);
        $outboxes->expects($this->once())->method('save');

        $handler = new SendNotificationHandler($templates, $messages, $outboxes, $preferences);

        $result = $handler->handle(new SendNotificationCommand(
            'leave.request.submitted',
            'user-1',
            Channel::InApp,
            ['name' => 'Alice'],
        ));

        $this->assertNotNull($result);
    }

    public function test_send_throws_when_template_not_found(): void
    {
        $handler = $this->makeSendHandler(null);

        $this->expectException(MessageTemplateNotFoundException::class);

        $handler->handle(new SendNotificationCommand('missing', 'user-1', Channel::InApp, []));
    }

    public function test_send_skips_when_opted_out(): void
    {
        $template = $this->makeTemplate();
        $templates = $this->createMock(MessageTemplateRepositoryInterface::class);
        $templates->method('findByCode')->willReturn($template);

        $pref = \App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreference::set(
            \App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreferenceId::generate(),
            'user-1',
            Channel::InApp,
            'leave.request.submitted',
            false,
        );

        $preferences = $this->createMock(UserNotificationPreferenceRepositoryInterface::class);
        $preferences->method('findByUserAndChannel')->willReturn($pref);

        $messages = $this->createMock(NotificationMessageRepositoryInterface::class);
        $messages->expects($this->never())->method('save');
        $outboxes = $this->createMock(NotificationOutboxRepositoryInterface::class);

        $handler = new SendNotificationHandler($templates, $messages, $outboxes, $preferences);

        $result = $handler->handle(new SendNotificationCommand(
            'leave.request.submitted',
            'user-1',
            Channel::InApp,
            ['name' => 'Alice'],
        ));

        $this->assertNull($result);
    }

    public function test_high_priority_bypasses_opt_out(): void
    {
        $template = $this->makeTemplate();
        $template2 = MessageTemplate::create(
            MessageTemplateId::generate(),
            'security.unauthorized.access',
            'Security',
            Channel::InApp,
            'Security alert',
            'Unauthorized access',
            [],
        );

        $templates = $this->createMock(MessageTemplateRepositoryInterface::class);
        $templates->method('findByCode')->willReturn($template2);

        $pref = \App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreference::set(
            \App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreferenceId::generate(),
            'user-1',
            Channel::InApp,
            null,
            false,
        );
        $preferences = $this->createMock(UserNotificationPreferenceRepositoryInterface::class);
        $preferences->method('findByUserAndChannel')->willReturn($pref);

        $messages = $this->createMock(NotificationMessageRepositoryInterface::class);
        $messages->expects($this->once())->method('save');
        $outboxes = $this->createMock(NotificationOutboxRepositoryInterface::class);
        $outboxes->expects($this->once())->method('save');

        $handler = new SendNotificationHandler($templates, $messages, $outboxes, $preferences);

        $result = $handler->handle(new SendNotificationCommand(
            'security.unauthorized.access',
            'user-1',
            Channel::InApp,
            [],
            NotificationPriority::High,
        ));

        $this->assertNotNull($result);
    }

    public function test_mark_read_forbids_other_user(): void
    {
        $template = $this->makeTemplate();
        $msg = NotificationMessage::create(
            NotificationMessageId::generate(),
            $template,
            'user-1',
            null,
            ['subject' => 'Test', 'body' => 'Body'],
            [],
        );

        $messages = $this->createMock(NotificationMessageRepositoryInterface::class);
        $messages->method('findById')->willReturn($msg);

        $handler = new MarkMessageReadHandler($messages);

        $this->expectException(\App\Modules\Notification\Domain\Exceptions\NotificationMessageNotFoundException::class);

        $handler->handle(new MarkMessageReadCommand((string) $msg->getId(), 'other-user'));
    }
}
