<?php

namespace App\Modules\Notification\Domain\Aggregates\NotificationOutbox;

use App\Modules\Notification\Domain\Aggregates\NotificationMessage\NotificationMessage;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Domain\ValueObjects\OutboxStatus;
use Carbon\CarbonImmutable;

class NotificationOutbox
{
    private const BACKOFF_BASE = 60;

    private function __construct(
        private readonly NotificationOutboxId $id,
        private string $notificationMessageId,
        private Channel $channel,
        private OutboxStatus $status,
        private int $attempts,
        private int $maxAttempts,
        private CarbonImmutable $availableAt,
        private ?CarbonImmutable $lockedAt,
        private ?string $lockedBy,
        private ?string $lastError,
    ) {}

    public static function create(NotificationOutboxId $id, NotificationMessage $message): self
    {
        return new self(
            $id,
            (string) $message->getId(),
            $message->getChannel(),
            OutboxStatus::Pending,
            0,
            3,
            CarbonImmutable::now(),
            null,
            null,
            null,
        );
    }

    public static function reconstitute(
        NotificationOutboxId $id,
        string $notificationMessageId,
        Channel $channel,
        OutboxStatus $status,
        int $attempts,
        int $maxAttempts,
        CarbonImmutable $availableAt,
        ?CarbonImmutable $lockedAt,
        ?string $lockedBy,
        ?string $lastError,
    ): self {
        return new self($id, $notificationMessageId, $channel, $status, $attempts, $maxAttempts, $availableAt, $lockedAt, $lockedBy, $lastError);
    }

    public function lock(string $workerId, CarbonImmutable $at): void
    {
        $this->status = OutboxStatus::Processing;
        $this->lockedAt = $at;
        $this->lockedBy = $workerId;
    }

    public function succeed(CarbonImmutable $at): void
    {
        $this->status = OutboxStatus::Sent;
        $this->lockedAt = null;
        $this->lockedBy = null;
    }

    public function fail(string $error, ?int $backoffSeconds = null): void
    {
        $this->attempts++;
        $this->status = OutboxStatus::Failed;
        $this->lastError = $error;
        $this->availableAt = CarbonImmutable::now()->addSeconds($backoffSeconds ?? $this->nextBackoffSeconds());
        $this->lockedAt = null;
        $this->lockedBy = null;
    }

    public function canRetry(): bool
    {
        return $this->attempts < $this->maxAttempts;
    }

    public function nextBackoffSeconds(): int
    {
        return self::BACKOFF_BASE * (2 ** $this->attempts);
    }

    public function getId(): NotificationOutboxId { return $this->id; }
    public function getNotificationMessageId(): string { return $this->notificationMessageId; }
    public function getChannel(): Channel { return $this->channel; }
    public function getStatus(): OutboxStatus { return $this->status; }
    public function getAttempts(): int { return $this->attempts; }
    public function getMaxAttempts(): int { return $this->maxAttempts; }
    public function getAvailableAt(): CarbonImmutable { return $this->availableAt; }
    public function getLockedAt(): ?CarbonImmutable { return $this->lockedAt; }
    public function getLockedBy(): ?string { return $this->lockedBy; }
    public function getLastError(): ?string { return $this->lastError; }
}
