<?php

namespace App\Modules\Identity\Infrastructure\Http\Controllers;

use App\Modules\Identity\Domain\Aggregates\Role\PermissionCode;
use App\Modules\Identity\Domain\Aggregates\Role\Role;
use App\Modules\Identity\Domain\Aggregates\Role\RoleCode;
use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use App\Modules\Identity\Domain\Aggregates\Role\RoleName;
use App\Modules\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\Identity\Infrastructure\Http\Requests\CreateRoleRequest;
use App\Modules\Identity\Infrastructure\Http\Requests\GrantRolePermissionRequest;
use App\Modules\Identity\Infrastructure\Http\Requests\UpdateRoleRequest;
use App\Modules\Identity\Infrastructure\Http\Resources\RoleResource;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\RoleModel;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController
{
    public function __construct(private RoleRepositoryInterface $roles) {}

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);

        $paginator = RoleModel::with('rolePermissions')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json(new PaginatedCollection($paginator->through(fn ($m) => new RoleResource($m))));
    }

    public function store(CreateRoleRequest $request): JsonResponse
    {
        $role = Role::create(
            RoleId::generate(),
            RoleCode::fromString($request->code),
            RoleName::fromString($request->name),
            $request->description,
        );

        $this->roles->save($role);
        $model = RoleModel::with('rolePermissions')->find((string) $role->id());

        return response()->json(['data' => new RoleResource($model)], 201);
    }

    public function show(string $id): JsonResponse
    {
        $model = RoleModel::with('rolePermissions')->find($id);
        abort_if(! $model, 404, 'Role not found');

        return response()->json(['data' => new RoleResource($model)]);
    }

    public function update(string $id, UpdateRoleRequest $request): JsonResponse
    {
        $role = $this->roles->findById(RoleId::fromString($id));
        abort_if(! $role, 404, 'Role not found');

        $role->update(RoleName::fromString($request->input('name', (string) $role->name())), $request->description);
        $this->roles->save($role);

        $model = RoleModel::with('rolePermissions')->find($id);
        return response()->json(['data' => new RoleResource($model)]);
    }

    public function activate(string $id): JsonResponse
    {
        $role = $this->roles->findById(RoleId::fromString($id));
        abort_if(! $role, 404, 'Role not found');
        $role->activate();
        $this->roles->save($role);

        $model = RoleModel::with('rolePermissions')->find($id);
        return response()->json(['data' => new RoleResource($model)]);
    }

    public function deactivate(string $id): JsonResponse
    {
        $role = $this->roles->findById(RoleId::fromString($id));
        abort_if(! $role, 404, 'Role not found');
        $role->deactivate();
        $this->roles->save($role);

        $model = RoleModel::with('rolePermissions')->find($id);
        return response()->json(['data' => new RoleResource($model)]);
    }

    public function grantPermission(string $id, GrantRolePermissionRequest $request): JsonResponse
    {
        $role = $this->roles->findById(RoleId::fromString($id));
        abort_if(! $role, 404, 'Role not found');
        $role->grantPermission(PermissionCode::fromString($request->permission_code));
        $this->roles->save($role);

        $model = RoleModel::with('rolePermissions')->find($id);
        return response()->json(['data' => new RoleResource($model)]);
    }

    public function revokePermission(string $id, string $code): JsonResponse
    {
        $role = $this->roles->findById(RoleId::fromString($id));
        abort_if(! $role, 404, 'Role not found');
        $role->revokePermission(PermissionCode::fromString($code));
        $this->roles->save($role);

        $model = RoleModel::with('rolePermissions')->find($id);
        return response()->json(['data' => new RoleResource($model)]);
    }
}
