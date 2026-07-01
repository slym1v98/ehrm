<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Repositories;

use App\Modules\Identity\Domain\Aggregates\Role\PermissionCode;
use App\Modules\Identity\Domain\Aggregates\Role\Role;
use App\Modules\Identity\Domain\Aggregates\Role\RoleCode;
use App\Modules\Identity\Domain\Aggregates\Role\RoleId;
use App\Modules\Identity\Domain\Aggregates\Role\RoleName;
use App\Modules\Identity\Domain\Aggregates\Role\RolePermission;
use App\Modules\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\PermissionModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\RoleModel;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\RolePermissionModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findById(RoleId $id): ?Role
    {
        $model = RoleModel::with('rolePermissions')->find((string) $id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByCode(RoleCode $code): ?Role
    {
        $model = RoleModel::with('rolePermissions')->where('code', (string) $code)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function save(Role $role): void
    {
        DB::transaction(function () use ($role) {
            RoleModel::updateOrCreate(
                ['id' => (string) $role->id()],
                [
                    'code' => (string) $role->code(),
                    'name' => (string) $role->name(),
                    'description' => $role->description(),
                    'active' => $role->isActive(),
                ],
            );

            $existing = RolePermissionModel::where('role_id', (string) $role->id())->pluck('permission_code');
            $desired = [];
            foreach ($role->permissions() as $rp) {
                $desired[] = (string) $rp->permissionCode;
            }

            foreach ($desired as $code) {
                if (! $existing->contains($code)) {
                    RolePermissionModel::create([
                        'role_id' => (string) $role->id(),
                        'permission_code' => $code,
                        'created_at' => now(),
                    ]);
                }
            }

            foreach ($existing as $code) {
                if (! in_array($code, $desired, true)) {
                    RolePermissionModel::where('role_id', (string) $role->id())
                        ->where('permission_code', $code)
                        ->delete();
                }
            }
        });

        foreach ($role->releaseEvents() as $event) {
            Event::dispatch($event);
        }
    }

    public function listPaginated(int $page, int $perPage): array
    {
        $paginator = RoleModel::with('rolePermissions')
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

    public function listPermissions(): array
    {
        return PermissionModel::orderBy('code')->get()->map(fn ($p) => [
            'code' => $p->code,
            'module' => $p->module,
            'action' => $p->action,
            'description' => $p->description,
            'active' => (bool) $p->active,
        ])->all();
    }

    private function toDomain(RoleModel $model): Role
    {
        $permissions = [];
        foreach ($model->rolePermissions as $rp) {
            $permissions[] = new RolePermission(
                PermissionCode::fromString($rp->permission_code),
                DateTimeImmutable::createFromMutable($rp->created_at->toDateTime()),
            );
        }

        return Role::reconstitute(
            id: RoleId::fromString($model->id),
            code: RoleCode::fromString($model->code),
            name: RoleName::fromString($model->name),
            description: $model->description,
            active: (bool) $model->active,
            permissions: $permissions,
        );
    }
}
