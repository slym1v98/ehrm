<?php

namespace App\Modules\Identity\Infrastructure\Http\Controllers;

use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use App\Modules\Identity\Domain\Aggregates\User\DataScope;
use App\Modules\Identity\Domain\Aggregates\User\Email;
use App\Modules\Identity\Domain\Aggregates\User\HashedPassword;
use App\Modules\Identity\Domain\Aggregates\User\ScopeType;
use App\Modules\Identity\Domain\Aggregates\User\User;
use App\Modules\Identity\Domain\Aggregates\User\UserId;
use App\Modules\Identity\Domain\Aggregates\User\UserName;
use App\Modules\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Infrastructure\Http\Requests\AssignRoleRequest;
use App\Modules\Identity\Infrastructure\Http\Requests\CreateUserRequest;
use App\Modules\Identity\Infrastructure\Http\Requests\UpdateUserRequest;
use App\Modules\Identity\Infrastructure\Http\Resources\UserResource;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);

        $paginator = UserModel::with(['userRoles.role', 'dataScopeAssignments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json(new PaginatedCollection($paginator->through(fn ($m) => new UserResource($m))));
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = User::create(
            UserId::generate(),
            Email::fromString($request->email),
            HashedPassword::fromHash(Hash::make($request->password)),
            UserName::fromString($request->name),
        );

        $this->users->save($user);
        $model = UserModel::with(['userRoles.role', 'dataScopeAssignments'])->find((string) $user->id());

        return response()->json(['data' => new UserResource($model)], 201);
    }

    public function show(string $id): JsonResponse
    {
        $model = UserModel::with(['userRoles.role', 'dataScopeAssignments'])->find($id);
        abort_if(! $model, 404, 'User not found');

        return response()->json(['data' => new UserResource($model)]);
    }

    public function update(string $id, UpdateUserRequest $request): JsonResponse
    {
        $model = UserModel::find($id);
        abort_if(! $model, 404, 'User not found');

        $model->update($request->only(['name', 'email']));
        $model->refresh()->load(['userRoles.role', 'dataScopeAssignments']);

        return response()->json(['data' => new UserResource($model)]);
    }

    public function disable(string $id): JsonResponse
    {
        $user = $this->users->findById(UserId::fromString($id));
        abort_if(! $user, 404, 'User not found');

        $user->disable();
        $this->users->save($user);

        $model = UserModel::with(['userRoles.role', 'dataScopeAssignments'])->find($id);
        return response()->json(['data' => new UserResource($model)]);
    }

    public function reactivate(string $id): JsonResponse
    {
        $user = $this->users->findById(UserId::fromString($id));
        abort_if(! $user, 404, 'User not found');

        $user->reactivate();
        $this->users->save($user);

        $model = UserModel::with(['userRoles.role', 'dataScopeAssignments'])->find($id);
        return response()->json(['data' => new UserResource($model)]);
    }

    public function assignRole(string $id, AssignRoleRequest $request): JsonResponse
    {
        $user = $this->users->findById(UserId::fromString($id));
        abort_if(! $user, 404, 'User not found');

        $role = $this->roles->findById(RoleId::fromString($request->role_id));
        abort_if(! $role, 404, 'Role not found');
        abort_if(! $role->isActive(), 409, 'Role is inactive');

        $assignedBy = $request->user()?->id ? UserId::fromString((string) $request->user()->id) : null;
        $user->assignRole($role->id(), $assignedBy);
        $user->grantDataScope(new DataScope(
            type: ScopeType::from($request->scope_type),
            branchId: $request->branch_id,
            departmentId: $request->department_id,
        ));

        $this->users->save($user);

        $model = UserModel::with(['userRoles.role', 'dataScopeAssignments'])->find($id);
        return response()->json(['data' => new UserResource($model)]);
    }

    public function revokeRole(string $id, string $roleId): JsonResponse
    {
        $user = $this->users->findById(UserId::fromString($id));
        abort_if(! $user, 404, 'User not found');

        $user->revokeRole(RoleId::fromString($roleId));
        $this->users->save($user);

        $model = UserModel::with(['userRoles.role', 'dataScopeAssignments'])->find($id);
        return response()->json(['data' => new UserResource($model)]);
    }
}
