<?php

namespace App\Modules\Workflow\Infrastructure\Http\Controllers;

use App\Modules\Workflow\Application\CommandHandlers\CreateWorkflowTemplateHandler;
use App\Modules\Workflow\Application\Commands\CreateWorkflowTemplateCommand;
use App\Modules\Workflow\Application\Queries\GetWorkflowTemplateQuery;
use App\Modules\Workflow\Application\Queries\ListWorkflowTemplatesQuery;
use App\Modules\Workflow\Application\QueryHandlers\GetWorkflowTemplateHandler;
use App\Modules\Workflow\Application\QueryHandlers\ListWorkflowTemplatesHandler;
use App\Modules\Workflow\Infrastructure\Http\Requests\CreateWorkflowTemplateRequest;
use App\Modules\Workflow\Infrastructure\Http\Resources\WorkflowTemplateResource;
use Illuminate\Routing\Controller;

class WorkflowTemplateController extends Controller
{
    public function index(ListWorkflowTemplatesHandler $handler)
    {
        return WorkflowTemplateResource::collection($handler->handle(new ListWorkflowTemplatesQuery()));
    }

    public function show(string $id, GetWorkflowTemplateHandler $handler)
    {
        $template = $handler->handle(new GetWorkflowTemplateQuery($id));
        return new WorkflowTemplateResource($template);
    }

    public function store(CreateWorkflowTemplateRequest $request, CreateWorkflowTemplateHandler $handler)
    {
        $template = $handler->handle(new CreateWorkflowTemplateCommand(
            $request->input('code'),
            $request->input('name'),
            $request->input('description'),
            $request->input('steps'),
        ));
        return new WorkflowTemplateResource($template);
    }
}
