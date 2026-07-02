<?php

use App\Modules\Notification\Infrastructure\Http\Controllers\MessageTemplateController;
use App\Modules\Notification\Infrastructure\Http\Controllers\NotificationController;
use App\Modules\Notification\Infrastructure\Http\Controllers\NotificationPreferenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('permission:notification.view-own');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->middleware('permission:notification.view-own');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->middleware('permission:notification.mark-read-own');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->middleware('permission:notification.mark-read-own');

    Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index'])->middleware('permission:notification.preference.manage-own');
    Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update'])->middleware('permission:notification.preference.manage-own');

    Route::get('/notification-templates', [MessageTemplateController::class, 'index'])->middleware('permission:notification.template.view');
    Route::post('/notification-templates', [MessageTemplateController::class, 'store'])->middleware('permission:notification.template.manage');
    Route::patch('/notification-templates/{id}', [MessageTemplateController::class, 'update'])->middleware('permission:notification.template.manage');
    Route::post('/notification-templates/{id}/activate', [MessageTemplateController::class, 'activate'])->middleware('permission:notification.template.manage');
    Route::post('/notification-templates/{id}/deactivate', [MessageTemplateController::class, 'deactivate'])->middleware('permission:notification.template.manage');
    Route::post('/notification-outbox/process', [NotificationController::class, 'processOutbox'])->middleware('permission:notification.outbox.process');
});
