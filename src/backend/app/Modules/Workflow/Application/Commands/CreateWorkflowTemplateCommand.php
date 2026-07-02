<?php

namespace App\Modules\Workflow\Application\Commands;

class CreateWorkflowTemplateCommand
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $steps,
    ) {}
}
