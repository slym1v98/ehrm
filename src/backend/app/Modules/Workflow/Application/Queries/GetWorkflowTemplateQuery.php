<?php

namespace App\Modules\Workflow\Application\Queries;

class GetWorkflowTemplateQuery
{
    public function __construct(public readonly string $id) {}
}
