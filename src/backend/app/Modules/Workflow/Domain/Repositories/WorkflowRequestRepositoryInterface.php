<?php

namespace App\Modules\Workflow\Domain\Repositories;

use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowRequest;
use App\Modules\Workflow\Domain\Aggregates\WorkflowRequest\WorkflowRequestId;

interface WorkflowRequestRepositoryInterface
{
    public function findById(WorkflowRequestId $id): ?WorkflowRequest;
    public function findBySubject(string $subjectType, string $subjectId): array;
    public function findByStatus(string $status): array;
    public function save(WorkflowRequest $request): void;
}
