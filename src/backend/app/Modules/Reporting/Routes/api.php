<?php

use App\Modules\Reporting\Infrastructure\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/reports', [ReportController::class, 'definitions'])->middleware('permission:report.definition.view');
    Route::post('/reports/{code}/runs', [ReportController::class, 'run'])->middleware('permission:report.run.create');
    Route::get('/report-runs', [ReportController::class, 'listRuns'])->middleware('permission:report.run.view-own');
    Route::get('/report-runs/{id}', [ReportController::class, 'showRun'])->middleware('permission:report.run.view-own');
});
