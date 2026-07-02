<?php

namespace App\Modules\Workflow\Domain\Aggregates\WorkflowRequest;

use Ramsey\Uuid\Uuid;

class WorkflowRequestId
{
    public function __construct(private string $value)
    {
        if ($value === '' || ! Uuid::isValid($value)) {
            throw new \InvalidArgumentException('WorkflowRequestId must be a valid UUID');
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
