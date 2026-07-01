<?php

use App\Modules\Audit\Infrastructure\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.log.list');
});
