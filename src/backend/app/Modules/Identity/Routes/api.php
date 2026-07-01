<?php

use App\Modules\Identity\Infrastructure\Http\Controllers\AuthController;
use App\Modules\Identity\Infrastructure\Http\Controllers\PermissionController;
use App\Modules\Identity\Infrastructure\Http\Controllers\RoleController;
use App\Modules\Identity\Infrastructure\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

        Route::get('/users', [UserController::class, 'index'])->middleware('permission:identity.user.list');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:identity.user.create');
        Route::get('/users/{id}', [UserController::class, 'show'])->middleware('permission:identity.user.view');
        Route::patch('/users/{id}', [UserController::class, 'update'])->middleware('permission:identity.user.update');
        Route::post('/users/{id}/disable', [UserController::class, 'disable'])->middleware('permission:identity.user.disable');
        Route::post('/users/{id}/reactivate', [UserController::class, 'reactivate'])->middleware('permission:identity.user.reactivate');
        Route::post('/users/{id}/roles', [UserController::class, 'assignRole'])->middleware('permission:identity.user.assign_role');
        Route::delete('/users/{id}/roles/{roleId}', [UserController::class, 'revokeRole'])->middleware('permission:identity.user.revoke_role');

        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:identity.role.list');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:identity.role.create');
        Route::get('/roles/{id}', [RoleController::class, 'show'])->middleware('permission:identity.role.view');
        Route::patch('/roles/{id}', [RoleController::class, 'update'])->middleware('permission:identity.role.update');
        Route::post('/roles/{id}/activate', [RoleController::class, 'activate'])->middleware('permission:identity.role.update');
        Route::post('/roles/{id}/deactivate', [RoleController::class, 'deactivate'])->middleware('permission:identity.role.update');
        Route::post('/roles/{id}/permissions', [RoleController::class, 'grantPermission'])->middleware('permission:identity.role.grant_permission');
        Route::delete('/roles/{id}/permissions/{code}', [RoleController::class, 'revokePermission'])->middleware('permission:identity.role.revoke_permission');

        Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:identity.permission.list');
    });
});
