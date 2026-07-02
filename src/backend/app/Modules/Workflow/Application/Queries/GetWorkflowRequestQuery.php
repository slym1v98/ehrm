<?php

namespace App\Modules\Workflow\Application\Queries;

class GetWorkflowRequestQuery
{
    public function __construct(public readonly string $id) {}
}
