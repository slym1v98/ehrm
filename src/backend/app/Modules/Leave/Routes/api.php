<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Leave\Infrastructure\Http\Controllers\LeaveTypeController;
use App\Modules\Leave\Infrastructure\Http\Controllers\LeavePolicyController;
use App\Modules\Leave\Infrastructure\Http\Controllers\LeaveRequestController;
use App\Modules\Leave\Infrastructure\Http\Controllers\LeaveBalanceController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Leave types
    Route::get('leave-types', [LeaveTypeController::class, 'index'])
        ->middleware('permission:leave.type.view');

    // Leave policies
    Route::get('leave-policies', [LeavePolicyController::class, 'index'])
        ->middleware('permission:leave.policy.view');

    // Leave requests
    Route::post('leave-requests', [LeaveRequestController::class, 'store'])
        ->middleware('permission:leave.request.create');
    Route::get('leave-requests', [LeaveRequestController::class, 'index'])
        ->middleware('permission:leave.request.view');
    Route::get('leave-requests/{id}', [LeaveRequestController::class, 'show'])
        ->middleware('permission:leave.request.view');
    Route::post('leave-requests/{id}/approve', [LeaveRequestController::class, 'approve'])
        ->middleware('permission:leave.request.approve');
    Route::post('leave-requests/{id}/reject', [LeaveRequestController::class, 'reject'])
        ->middleware('permission:leave.request.reject');
    Route::post('leave-requests/{id}/cancel', [LeaveRequestController::class, 'cancel'])
        ->middleware('permission:leave.request.cancel');

    // Leave balances
    Route::get('leave-balances', [LeaveBalanceController::class, 'index'])
        ->middleware('permission:leave.balance.view');
    Route::get('leave-balances/summary', [LeaveBalanceController::class, 'summary'])
        ->middleware('permission:leave.balance.view');
});
