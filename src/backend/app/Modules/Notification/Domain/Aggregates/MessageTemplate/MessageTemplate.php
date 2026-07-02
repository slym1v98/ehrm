<?php

namespace App\Modules\Notification\Domain\Aggregates\MessageTemplate;

use App\Modules\Notification\Domain\ValueObjects\Channel;

class MessageTemplate
{
    private function __construct(
        private readonly MessageTemplateId $id,
        private readonly string $code,
        private string $name,
        private readonly Channel $channel,
        private string $subject,
        private string $body,
        private array $variables,
        private bool $active,
    ) {}

    public static function create(
        MessageTemplateId $id,
        string $code,
        string $name,
        Channel $channel,
        string $subject,
        string $body,
        array $variables = [],
        bool $active = true,
    ): self {
        return new self($id, $code, $name, $channel, $subject, $body, $variables, $active);
    }

    public function update(
        string $name,
        string $subject,
        string $body,
        array $variables,
        bool $active,
    ): void {
        $this->name = $name;
        $this->subject = $subject;
        $this->body = $body;
        $this->variables = $variables;
        $this->active = $active;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function render(array $params): array
    {
        $placeholderMap = [];
        foreach ($params as $key => $value) {
            $placeholderMap['{{'.$key.'}}'] = (string) $value;
        }

        $subject = $this->subject !== null
            ? str_replace(array_keys($placeholderMap), array_values($placeholderMap), $this->subject)
            : null;
        $body = str_replace(array_keys($placeholderMap), array_values($placeholderMap), $this->body);

        return ['subject' => $subject, 'body' => $body];
    }

    public function getId(): MessageTemplateId { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getChannel(): Channel { return $this->channel; }
    public function getSubject(): string { return $this->subject; }
    public function getBody(): string { return $this->body; }
    public function getVariables(): array { return $this->variables; }
    public function isActive(): bool { return $this->active; }
}
