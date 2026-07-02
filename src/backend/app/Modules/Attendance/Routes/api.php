<?php

use App\Modules\Attendance\Infrastructure\Http\Controllers\AttendanceAdjustmentController;
use App\Modules\Attendance\Infrastructure\Http\Controllers\AttendancePeriodController;
use App\Modules\Attendance\Infrastructure\Http\Controllers\AttendanceRawLogController;
use App\Modules\Attendance\Infrastructure\Http\Controllers\AttendanceTimesheetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/attendance/raw-logs', [AttendanceRawLogController::class, 'index'])->middleware('permission:attendance.raw-log.view');
    Route::post('/attendance/raw-logs', [AttendanceRawLogController::class, 'store'])->middleware('permission:attendance.raw-log.create');

    Route::get('/attendance/timesheets', [AttendanceTimesheetController::class, 'index'])->middleware('permission:attendance.timesheet.view');
    Route::get('/employees/{id}/attendance', [AttendanceTimesheetController::class, 'employeeAttendance'])->middleware('permission:attendance.timesheet.view');
    Route::post('/attendance/calculate', [AttendanceTimesheetController::class, 'calculate'])->middleware('permission:attendance.timesheet.calculate');

    Route::get('/attendance-adjustment-requests', [AttendanceAdjustmentController::class, 'index'])->middleware('permission:attendance.adjustment.approve');
    Route::post('/attendance-adjustment-requests', [AttendanceAdjustmentController::class, 'store'])->middleware('permission:attendance.adjustment.create');
    Route::post('/attendance-adjustment-requests/{id}/approve', [AttendanceAdjustmentController::class, 'approve'])->middleware('permission:attendance.adjustment.approve');
    Route::post('/attendance-adjustment-requests/{id}/reject', [AttendanceAdjustmentController::class, 'reject'])->middleware('permission:attendance.adjustment.approve');

    Route::get('/attendance-periods', [AttendancePeriodController::class, 'index'])->middleware('permission:attendance.period.manage');
    Route::post('/attendance-periods', [AttendancePeriodController::class, 'store'])->middleware('permission:attendance.period.manage');
    Route::post('/attendance-periods/{id}/close', [AttendancePeriodController::class, 'close'])->middleware('permission:attendance.period.manage');
    Route::post('/attendance-periods/{id}/reopen', [AttendancePeriodController::class, 'reopen'])->middleware('permission:attendance.period.manage');
});
