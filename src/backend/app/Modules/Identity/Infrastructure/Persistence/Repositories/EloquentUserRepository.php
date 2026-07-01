<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Repositories;

use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use App\Modules\Identity\Domain\Aggregates\User\DataScope;
use App\Modules\Identity\Domain\Aggregates\User\DataScopeAssignment;
use App\Modules\Identity\Domain\Aggregates\User\Email;
use App\Modules\Identity\Domain\Aggregates\User\EmployeeId;
use App\Modules\Identity\Domain\Aggregates\User\HashedPassword;
use App\Modules\Identity\Domain\Aggregates\User\RoleBinding;
use App\Modules\Identity\Domain\Aggregates\User\ScopeType;
use App\Modules\Identity\Domain\Aggregates\User\User;
use App\Modules\Identity\Domain\Aggregates\User\UserId;
use App\Modules\Identity\Domain\Aggregates\User\UserName;
use App\Modules\Identity\Domain\Aggregates\User\UserStatus;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\DataScopeAssignmentModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserRoleModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?User
    {
        $model = UserModel::with(['userRoles', 'dataScopeAssignments'])->find((string) $id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $model = UserModel::with(['userRoles', 'dataScopeAssignments'])->where('email', (string) $email)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function existsByEmail(Email $email): bool
    {
        return UserModel::where('email', (string) $email)->exists();
    }

    public function save(User $user): void
    {
        DB::transaction(function () use ($user) {
            UserModel::updateOrCreate(
                ['id' => (string) $user->id()],
                [
                    'employee_id' => $user->employeeId() ? (string) $user->employeeId() : null,
                    'name' => (string) $user->name(),
                    'email' => (string) $user->email(),
                    'password' => (string) $user->passwordHash(),
                    'status' => $user->status()->value,
                    'last_login_at' => $user->lastLoginAt(),
                ],
            );

            $existingRoles = UserRoleModel::where('user_id', (string) $user->id())->get()->keyBy('role_id');
            $seenRoleIds = [];
            foreach ($user->roleBindings() as $binding) {
                $key = (string) $binding->roleId;
                $seenRoleIds[] = $key;
                if ($existing = $existingRoles->get($key)) {
                    $existing->revoked_at = $binding->revokedAt();
                    $existing->save();
                } else {
                    UserRoleModel::create([
                        'user_id' => (string) $user->id(),
                        'role_id' => $key,
                        'assigned_by' => $binding->assignedBy ? (string) $binding->assignedBy : null,
                        'assigned_at' => $binding->assignedAt,
                        'revoked_at' => $binding->revokedAt(),
                    ]);
                }
            }

            $existingScopes = DataScopeAssignmentModel::where('user_id', (string) $user->id())->pluck('id');
            foreach ($user->dataScopeAssignments() as $assignment) {
                if (! $existingScopes->contains($assignment->id)) {
                    DataScopeAssignmentModel::create([
                        'id' => $assignment->id,
                        'user_id' => (string) $user->id(),
                        'scope_type' => $assignment->scope->type->value,
                        'branch_id' => $assignment->scope->branchId,
                        'department_id' => $assignment->scope->departmentId,
                        'effective_from' => $assignment->scope->effectiveFrom,
                        'effective_to' => $assignment->scope->effectiveTo,
                    ]);
                }
            }
        });

        foreach ($user->releaseEvents() as $event) {
            Event::dispatch($event);
        }
    }

    public function listPaginated(int $page, int $perPage): array
    {
        $paginator = UserModel::with(['userRoles', 'dataScopeAssignments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $model) {
            $items[] = $this->toDomain($model);
        }

        return [
            'items' => $items,
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    private function toDomain(UserModel $model): User
    {
        $roleBindings = [];
        foreach ($model->userRoles as $ur) {
            $roleBindings[] = new RoleBinding(
                RoleId::fromString($ur->role_id),
                $ur->assigned_by ? UserId::fromString($ur->assigned_by) : null,
                DateTimeImmutable::createFromMutable($ur->assigned_at instanceof \DateTime ? $ur->assigned_at : $ur->assigned_at->toDateTime()),
                $ur->revoked_at ? DateTimeImmutable::createFromMutable($ur->revoked_at instanceof \DateTime ? $ur->revoked_at : $ur->revoked_at->toDateTime()) : null,
            );
        }

        $scopeAssignments = [];
        foreach ($model->dataScopeAssignments as $sa) {
            $scopeAssignments[] = new DataScopeAssignment(
                id: $sa->id,
                scope: new DataScope(
                    type: ScopeType::from($sa->scope_type),
                    branchId: $sa->branch_id,
                    departmentId: $sa->department_id,
                    effectiveFrom: $sa->effective_from ? DateTimeImmutable::createFromMutable($sa->effective_from->toDateTime()) : null,
                    effectiveTo: $sa->effective_to ? DateTimeImmutable::createFromMutable($sa->effective_to->toDateTime()) : null,
                ),
                createdAt: DateTimeImmutable::createFromMutable($sa->created_at->toDateTime()),
            );
        }

        return User::reconstitute(
            id: UserId::fromString($model->id),
            employeeId: $model->employee_id ? EmployeeId::fromString($model->employee_id) : null,
            email: Email::fromString($model->email),
            passwordHash: HashedPassword::fromHash($model->password),
            name: UserName::fromString($model->name),
            status: UserStatus::from($model->status),
            lastLoginAt: $model->last_login_at ? DateTimeImmutable::createFromMutable($model->last_login_at->toDateTime()) : null,
            roleBindings: $roleBindings,
            dataScopeAssignments: $scopeAssignments,
        );
    }
}
