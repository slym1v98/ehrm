<?php

use App\Modules\Configuration\Http\Controllers\LookupController;
use App\Modules\Configuration\Http\Controllers\CodeGenerationRuleController;
use App\Modules\Configuration\Http\Controllers\HolidayCalendarController;
use App\Modules\Configuration\Http\Controllers\NotificationThresholdController;
use App\Modules\Configuration\Http\Controllers\SystemSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1/config')->group(function () {
    Route::get('lookups', [LookupController::class, 'index'])->middleware('permission:configuration.lookup.list');
    Route::post('lookups', [LookupController::class, 'store'])->middleware('permission:configuration.lookup.manage');
    Route::get('lookups/{id}', [LookupController::class, 'show'])->middleware('permission:configuration.lookup.list');
    Route::post('lookups/{id}/values', [LookupController::class, 'storeValue'])->middleware('permission:configuration.lookup.manage');

    Route::get('code-generation-rules', [CodeGenerationRuleController::class, 'index'])->middleware('permission:configuration.code_generation.list');
    Route::post('code-generation-rules', [CodeGenerationRuleController::class, 'store'])->name('config.code_rules.store')->middleware('permission:configuration.code_generation.manage');
    Route::post('code-generation-rules/{entityType}/preview', [CodeGenerationRuleController::class, 'preview'])->middleware('permission:configuration.code_generation.list');
    Route::post('code-generation-rules/{entityType}/next', [CodeGenerationRuleController::class, 'next'])->middleware('permission:configuration.code_generation.manage');

    Route::get('settings', [SystemSettingController::class, 'index'])->middleware('permission:configuration.setting.list');
    Route::post('settings', [SystemSettingController::class, 'store'])->name('config.settings.store')->middleware('permission:configuration.setting.manage');

    Route::get('holiday-calendars', [HolidayCalendarController::class, 'index'])->middleware('permission:configuration.holiday.list');
    Route::post('holiday-calendars', [HolidayCalendarController::class, 'store'])->name('config.holidays.store')->middleware('permission:configuration.holiday.manage');
    Route::post('holiday-calendars/{id}/holidays', [HolidayCalendarController::class, 'storeHoliday'])->name('config.holidays.value.store')->middleware('permission:configuration.holiday.manage');

    Route::get('notification-thresholds', [NotificationThresholdController::class, 'index'])->middleware('permission:configuration.notification_threshold.list');
    Route::post('notification-thresholds', [NotificationThresholdController::class, 'store'])->name('config.thresholds.store')->middleware('permission:configuration.notification_threshold.manage');
});
