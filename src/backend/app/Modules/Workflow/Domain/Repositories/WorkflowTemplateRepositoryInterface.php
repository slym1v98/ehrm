<?php

namespace App\Modules\Workflow\Domain\Repositories;

use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplate;
use App\Modules\Workflow\Domain\Aggregates\WorkflowTemplate\WorkflowTemplateId;

interface WorkflowTemplateRepositoryInterface
{
    public function findById(WorkflowTemplateId $id): ?WorkflowTemplate;
    public function findByCode(string $code): ?WorkflowTemplate;
    public function allActive(): array;
    public function save(WorkflowTemplate $template): void;
}
