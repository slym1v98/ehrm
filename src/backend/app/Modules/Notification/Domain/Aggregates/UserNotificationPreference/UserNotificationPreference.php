<?php

namespace App\Modules\Notification\Domain\Aggregates\UserNotificationPreference;

use App\Modules\Notification\Domain\ValueObjects\Channel;

class UserNotificationPreference
{
    private function __construct(
        private readonly UserNotificationPreferenceId $id,
        private string $userId,
        private Channel $channel,
        private ?string $templateCode,
        private bool $enabled,
    ) {}

    public static function set(
        UserNotificationPreferenceId $id,
        string $userId,
        Channel $channel,
        ?string $templateCode = null,
        bool $enabled = true,
    ): self {
        return new self($id, $userId, $channel, $templateCode, $enabled);
    }

    public function toggle(): void
    {
        $this->enabled = !$this->enabled;
    }

    public function matches(Channel $channel, string $templateCode): bool
    {
        if ($this->channel !== $channel) {
            return false;
        }
        if ($this->templateCode !== null && $this->templateCode !== $templateCode) {
            return false;
        }
        return true;
    }

    public function getId(): UserNotificationPreferenceId { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getChannel(): Channel { return $this->channel; }
    public function getTemplateCode(): ?string { return $this->templateCode; }
    public function isEnabled(): bool { return $this->enabled; }
}
