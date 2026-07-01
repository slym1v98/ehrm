<?php

namespace App\Modules\Identity\Infrastructure\Http\Controllers;

use App\Modules\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\Identity\Infrastructure\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;

class PermissionController
{
    public function __construct(private RoleRepositoryInterface $roles) {}

    public function index(): JsonResponse
    {
        $permissions = $this->roles->listPermissions();

        return response()->json([
            'data' => collect($permissions)->map(fn ($p) => new PermissionResource((object) $p)),
        ]);
    }
}
