<?php

namespace App\Modules\Workflow\Domain\Aggregates\WorkflowRequest;

use Ramsey\Uuid\Uuid;

class WorkflowActionId
{
    public function __construct(private string $value)
    {
        if ($value === '' || ! Uuid::isValid($value)) {
            throw new \InvalidArgumentException('WorkflowActionId must be a valid UUID');
        }
    }

    public static function new(): self
    {
        return new self((string) Uuid::uuid4());
    }

    public function value(): string
    {
        return $this->value;
    }
}
