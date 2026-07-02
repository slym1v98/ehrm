<?php

namespace App\Modules\Workflow\Infrastructure\Http\Controllers;

use App\Modules\Workflow\Application\CommandHandlers\ApproveWorkflowStepHandler;
use App\Modules\Workflow\Application\CommandHandlers\CancelWorkflowRequestHandler;
use App\Modules\Workflow\Application\CommandHandlers\RejectWorkflowStepHandler;
use App\Modules\Workflow\Application\CommandHandlers\ReturnWorkflowForEditHandler;
use App\Modules\Workflow\Application\CommandHandlers\SubmitWorkflowRequestHandler;
use App\Modules\Workflow\Application\Commands\ApproveWorkflowStepCommand;
use App\Modules\Workflow\Application\Commands\CancelWorkflowRequestCommand;
use App\Modules\Workflow\Application\Commands\RejectWorkflowStepCommand;
use App\Modules\Workflow\Application\Commands\ReturnWorkflowForEditCommand;
use App\Modules\Workflow\Application\Commands\SubmitWorkflowRequestCommand;
use App\Modules\Workflow\Application\Queries\GetWorkflowRequestQuery;
use App\Modules\Workflow\Application\Queries\ListWorkflowRequestsQuery;
use App\Modules\Workflow\Application\QueryHandlers\GetWorkflowRequestHandler;
use App\Modules\Workflow\Application\QueryHandlers\ListWorkflowRequestsHandler;
use App\Modules\Workflow\Infrastructure\Http\Requests\DecisionRequest;
use App\Modules\Workflow\Infrastructure\Http\Requests\StartWorkflowRequestRequest;
use App\Modules\Workflow\Infrastructure\Http\Resources\WorkflowRequestResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WorkflowRequestController extends Controller
{
    public function __construct(private ?string $actorId = null) {}

    public function store(StartWorkflowRequestRequest $request, SubmitWorkflowRequestHandler $handler)
    {
        $actorId = $request->user()?->id ?? $request->input('submitted_by');
        $wr = $handler->handle(new SubmitWorkflowRequestCommand(
            $request->input('workflow_template_id'),
            $request->input('subject_type'),
            $request->input('subject_id'),
            $actorId,
        ));
        return new WorkflowRequestResource($wr);
    }

    public function index(Request $request, ListWorkflowRequestsHandler $handler)
    {
        return WorkflowRequestResource::collection($handler->handle(new ListWorkflowRequestsQuery(
            $request->query('status'),
            $request->query('subject_type'),
            $request->query('subject_id'),
        )));
    }

    public function show(string $id, GetWorkflowRequestHandler $handler)
    {
        return new WorkflowRequestResource($handler->handle(new GetWorkflowRequestQuery($id)));
    }

    public function approve(string $id, DecisionRequest $request, ApproveWorkflowStepHandler $handler)
    {
        $handler->handle(new ApproveWorkflowStepCommand($id, $request->user()->getAuthIdentifier(), $request->input('comment')));
        return response()->noContent();
    }

    public function reject(string $id, DecisionRequest $request, RejectWorkflowStepHandler $handler)
    {
        $handler->handle(new RejectWorkflowStepCommand($id, $request->user()->getAuthIdentifier(), $request->input('comment')));
        return response()->noContent();
    }

    public function returnForEdit(string $id, DecisionRequest $request, ReturnWorkflowForEditHandler $handler)
    {
        $handler->handle(new ReturnWorkflowForEditCommand($id, $request->user()->getAuthIdentifier(), $request->input('comment')));
        return response()->noContent();
    }

    public function cancel(string $id, DecisionRequest $request, CancelWorkflowRequestHandler $handler)
    {
        $handler->handle(new CancelWorkflowRequestCommand($id, $request->user()->getAuthIdentifier(), $request->input('comment')));
        return response()->noContent();
    }
}
