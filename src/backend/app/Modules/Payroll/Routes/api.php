<?php

use App\Modules\Payroll\Infrastructure\Http\Controllers\{
    PayrollPeriodController, PayrollRunController, PayrollEntryController,
    PayrollAdjustmentController, PayslipController, PayrollComponentController
};
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Periods
    Route::get('/payroll/periods', [PayrollPeriodController::class, 'index'])->middleware('permission:payroll.period.view');
    Route::post('/payroll/periods', [PayrollPeriodController::class, 'store'])->middleware('permission:payroll.period.manage');
    Route::get('/payroll/periods/{id}', [PayrollPeriodController::class, 'show'])->middleware('permission:payroll.period.view');
    Route::post('/payroll/periods/{id}/submit-approval', [PayrollPeriodController::class, 'submitApproval'])->middleware('permission:payroll.period.manage');
    Route::post('/payroll/periods/{id}/approve', [PayrollPeriodController::class, 'approve'])->middleware('permission:payroll.approve');
    Route::post('/payroll/periods/{id}/reject', [PayrollPeriodController::class, 'reject'])->middleware('permission:payroll.approve');
    Route::post('/payroll/periods/{id}/lock', [PayrollPeriodController::class, 'lock'])->middleware('permission:payroll.lock');
    Route::post('/payroll/periods/{id}/reopen', [PayrollPeriodController::class, 'reopen'])->middleware('permission:payroll.period.manage');

    // Runs
    Route::post('/payroll/periods/{periodId}/start-run', [PayrollRunController::class, 'start'])->middleware('permission:payroll.run.start');

    // Entries
    Route::get('/payroll/periods/{periodId}/entries', [PayrollEntryController::class, 'index'])->middleware('permission:payroll.entry.view');
    Route::get('/payroll/entries/{id}', [PayrollEntryController::class, 'show'])->middleware('permission:payroll.entry.view');
    Route::post('/payroll/entries/{id}/review', [PayrollEntryController::class, 'review'])->middleware('permission:payroll.entry.review');

    // Adjustments
    Route::get('/payroll/entries/{entryId}/adjustments', [PayrollAdjustmentController::class, 'index'])->middleware('permission:payroll.entry.view');
    Route::post('/payroll/entries/{entryId}/adjustments', [PayrollAdjustmentController::class, 'store'])->middleware('permission:payroll.adjustment.manage');
    Route::post('/payroll/adjustments/{id}/approve', [PayrollAdjustmentController::class, 'approve'])->middleware('permission:payroll.adjustment.manage');
    Route::post('/payroll/adjustments/{id}/reject', [PayrollAdjustmentController::class, 'reject'])->middleware('permission:payroll.adjustment.manage');

    // Payslips
    Route::get('/payroll/payslips', [PayslipController::class, 'index']);
    Route::get('/payroll/payslips/{id}', [PayslipController::class, 'show']);
    Route::get('/payroll/payslips/{id}/download', [PayslipController::class, 'download']);
    Route::post('/payroll/periods/{periodId}/publish', [PayslipController::class, 'publish'])->middleware('permission:payroll.publish');

    // Components
    Route::get('/payroll/components', [PayrollComponentController::class, 'index'])->middleware('permission:payroll.period.view');
    Route::post('/payroll/components', [PayrollComponentController::class, 'store'])->middleware('permission:payroll.component.manage');
    Route::patch('/payroll/components/{id}', [PayrollComponentController::class, 'update'])->middleware('permission:payroll.component.manage');
    Route::delete('/payroll/components/{id}', [PayrollComponentController::class, 'destroy'])->middleware('permission:payroll.component.manage');
});
