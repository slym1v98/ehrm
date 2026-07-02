<?php

namespace App\Modules\Workflow\Application\Queries;

class ListWorkflowRequestsQuery
{
    public function __construct(public readonly ?string $status = null, public readonly ?string $subjectType = null, public readonly ?string $subjectId = null) {}
}
