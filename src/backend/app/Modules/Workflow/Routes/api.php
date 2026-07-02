<?php

use App\Modules\Workflow\Infrastructure\Http\Controllers\WorkflowRequestController;
use App\Modules\Workflow\Infrastructure\Http\Controllers\WorkflowTemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('workflow-templates', [WorkflowTemplateController::class, 'store'])->middleware('permission:workflow.template.create');
    Route::get('workflow-templates', [WorkflowTemplateController::class, 'index'])->middleware('permission:workflow.template.view');
    Route::get('workflow-templates/{id}', [WorkflowTemplateController::class, 'show'])->middleware('permission:workflow.template.view');

    Route::post('workflow-requests', [WorkflowRequestController::class, 'store'])->middleware('permission:workflow.request.start');
    Route::get('workflow-requests', [WorkflowRequestController::class, 'index'])->middleware('permission:workflow.request.view');
    Route::get('workflow-requests/{id}', [WorkflowRequestController::class, 'show'])->middleware('permission:workflow.request.view');
    Route::post('workflow-requests/{id}/approve', [WorkflowRequestController::class, 'approve'])->middleware('permission:workflow.request.approve');
    Route::post('workflow-requests/{id}/reject', [WorkflowRequestController::class, 'reject'])->middleware('permission:workflow.request.reject');
    Route::post('workflow-requests/{id}/return-for-edit', [WorkflowRequestController::class, 'returnForEdit'])->middleware('permission:workflow.request.return');
    Route::post('workflow-requests/{id}/cancel', [WorkflowRequestController::class, 'cancel'])->middleware('permission:workflow.request.cancel');
});
