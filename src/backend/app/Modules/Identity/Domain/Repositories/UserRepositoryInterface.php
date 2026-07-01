<?php

namespace App\Modules\Identity\Domain\Repositories;

use App\Modules\Identity\Domain\Aggregates\User\Email;
use App\Modules\Identity\Domain\Aggregates\User\User;
use App\Modules\Identity\Domain\Aggregates\User\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;
    public function findByEmail(Email $email): ?User;
    public function existsByEmail(Email $email): bool;
    public function save(User $user): void;

    /** @return array{items: User[], total: int, page: int, per_page: int} */
    public function listPaginated(int $page, int $perPage): array;
}
