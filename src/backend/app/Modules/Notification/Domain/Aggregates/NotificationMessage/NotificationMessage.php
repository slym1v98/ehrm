<?php

namespace App\Modules\Notification\Domain\Aggregates\NotificationMessage;

use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplate;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Domain\ValueObjects\MessageStatus;
use App\Modules\Notification\Domain\ValueObjects\NotificationPriority;
use Carbon\CarbonImmutable;

class NotificationMessage
{
    private function __construct(
        private readonly NotificationMessageId $id,
        private string $templateCode,
        private Channel $channel,
        private string $recipientUserId,
        private ?string $recipientAddress,
        private ?string $subjectRendered,
        private string $bodyRendered,
        private array $payload,
        private MessageStatus $status,
        private NotificationPriority $priority,
        private ?string $error,
        private ?CarbonImmutable $readAt,
        private ?CarbonImmutable $sentAt,
    ) {}

    public static function create(
        NotificationMessageId $id,
        MessageTemplate $template,
        string $recipientUserId,
        ?string $recipientAddress,
        array $rendered,
        array $payload,
        NotificationPriority $priority = NotificationPriority::Normal,
    ): self {
        return new self(
            $id,
            $template->getCode(),
            $template->getChannel(),
            $recipientUserId,
            $recipientAddress,
            $rendered['subject'] ?? null,
            $rendered['body'],
            $payload,
            MessageStatus::Pending,
            $priority,
            null,
            null,
            null,
        );
    }

    public static function reconstitute(
        NotificationMessageId $id,
        string $templateCode,
        Channel $channel,
        string $recipientUserId,
        ?string $recipientAddress,
        ?string $subjectRendered,
        string $bodyRendered,
        array $payload,
        MessageStatus $status,
        NotificationPriority $priority,
        ?string $error,
        ?CarbonImmutable $readAt,
        ?CarbonImmutable $sentAt,
    ): self {
        return new self($id, $templateCode, $channel, $recipientUserId, $recipientAddress, $subjectRendered, $bodyRendered, $payload, $status, $priority, $error, $readAt, $sentAt);
    }

    public function markRead(CarbonImmutable $at): void
    {
        if ($this->readAt !== null) {
            throw new \InvalidArgumentException('Message already marked as read');
        }
        $this->readAt = $at;
    }

    public function markSent(CarbonImmutable $at): void
    {
        if ($this->sentAt !== null) {
            throw new \InvalidArgumentException('Message already marked as sent');
        }
        $this->status = MessageStatus::Sent;
        $this->sentAt = $at;
    }

    public function markFailed(string $error): void
    {
        $this->status = MessageStatus::Failed;
        $this->error = $error;
    }

    public function getId(): NotificationMessageId { return $this->id; }
    public function getTemplateCode(): string { return $this->templateCode; }
    public function getChannel(): Channel { return $this->channel; }
    public function getRecipientUserId(): string { return $this->recipientUserId; }
    public function getRecipientAddress(): ?string { return $this->recipientAddress; }
    public function getSubjectRendered(): ?string { return $this->subjectRendered; }
    public function getBodyRendered(): string { return $this->bodyRendered; }
    public function getPayload(): array { return $this->payload; }
    public function getStatus(): MessageStatus { return $this->status; }
    public function getPriority(): NotificationPriority { return $this->priority; }
    public function getError(): ?string { return $this->error; }
    public function getReadAt(): ?CarbonImmutable { return $this->readAt; }
    public function getSentAt(): ?CarbonImmutable { return $this->sentAt; }
}
