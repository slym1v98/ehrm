<?php

namespace Tests\Unit\Modules\Notification\Domain;

use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplateId;
use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessageId;
use App\Modules\Notification\Domain\Aggregates\NotificationOutbox\NotificationOutboxId;
use App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreferenceId;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Domain\ValueObjects\MessageStatus;
use App\Modules\Notification\Domain\ValueObjects\NotificationPriority;
use App\Modules\Notification\Domain\ValueObjects\OutboxStatus;
use PHPUnit\Framework\TestCase;
use ValueError;

class ValueObjectsTest extends TestCase
{
    public function test_ids_round_trip(): void
    {
        foreach ([MessageTemplateId::class, NotificationMessageId::class, UserNotificationPreferenceId::class, NotificationOutboxId::class] as $class) {
            $id = $class::generate();

            $this->assertTrue($id->equals($class::fromString((string) $id)));
            $this->assertNotSame('', (string) $id);
        }
    }

    public function test_enum_values(): void
    {
        $this->assertSame('in_app', Channel::InApp->value);
        $this->assertSame('email', Channel::Email->value);
        $this->assertSame('sms', Channel::Sms->value);
        $this->assertSame('pending', MessageStatus::Pending->value);
        $this->assertSame('processing', OutboxStatus::Processing->value);
    }

    public function test_invalid_enum_value_is_rejected(): void
    {
        $this->expectException(ValueError::class);

        Channel::from('push');
    }

    public function test_priority_ordering(): void
    {
        $this->assertGreaterThan(NotificationPriority::Normal->weight(), NotificationPriority::High->weight());
        $this->assertGreaterThan(NotificationPriority::Low->weight(), NotificationPriority::Normal->weight());
    }
}
