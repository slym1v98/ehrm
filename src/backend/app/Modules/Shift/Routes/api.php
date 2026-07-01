<?php

use App\Modules\Shift\Infrastructure\Http\Controllers\ShiftAssignmentController;
use App\Modules\Shift\Infrastructure\Http\Controllers\ShiftTemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/shift-templates', [ShiftTemplateController::class, 'index'])->middleware('permission:shift.template.view');
    Route::post('/shift-templates', [ShiftTemplateController::class, 'store'])->middleware('permission:shift.template.create');
    Route::get('/shift-templates/{id}', [ShiftTemplateController::class, 'show'])->middleware('permission:shift.template.view');
    Route::patch('/shift-templates/{id}', [ShiftTemplateController::class, 'update'])->middleware('permission:shift.template.update');
    Route::post('/shift-templates/{id}/activate', [ShiftTemplateController::class, 'activate'])->middleware('permission:shift.template.update');
    Route::post('/shift-templates/{id}/deactivate', [ShiftTemplateController::class, 'deactivate'])->middleware('permission:shift.template.update');

    Route::post('/shift-assignments', [ShiftAssignmentController::class, 'store'])->middleware('permission:shift.template.update');
    Route::patch('/shift-assignments/{id}', [ShiftAssignmentController::class, 'update'])->middleware('permission:shift.template.update');
    Route::post('/shift-assignments/{id}/end', [ShiftAssignmentController::class, 'end'])->middleware('permission:shift.template.update');

    Route::get('/employees/{id}/shifts', [ShiftAssignmentController::class, 'employeeShifts'])->middleware('permission:shift.template.view');
    Route::get('/departments/{id}/shifts', [ShiftAssignmentController::class, 'departmentShifts'])->middleware('permission:shift.template.view');
});
