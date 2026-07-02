<?php

namespace App\Modules\Workflow\Application\Queries;

class ListWorkflowTemplatesQuery
{
    public function __construct(public readonly bool $includeInactive = false) {}
}
