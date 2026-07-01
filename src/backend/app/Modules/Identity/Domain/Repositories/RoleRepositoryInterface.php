<?php

namespace App\Modules\Identity\Domain\Repositories;

use App\Modules\Identity\Domain\Aggregates\Role\Role;
use App\Modules\Identity\Domain\Aggregates\Role\RoleCode;
use App\Modules\Identity\Domain\Aggregates\Role\RoleId;

interface RoleRepositoryInterface
{
    public function findById(RoleId $id): ?Role;
    public function findByCode(RoleCode $code): ?Role;
    public function save(Role $role): void;

    /** @return array{items: Role[], total: int, page: int, per_page: int} */
    public function listPaginated(int $page, int $perPage): array;

    /** @return array<int, array{code: string, module: string, action: string, description: ?string, active: bool}> */
    public function listPermissions(): array;
}
