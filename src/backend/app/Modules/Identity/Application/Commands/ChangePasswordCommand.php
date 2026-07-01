<?php

namespace App\Modules\Identity\Application\Commands;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;

final readonly class ChangePasswordCommand
{
    public function __construct(
        public UserModel $user,
        public string $currentPassword,
        public string $newPassword,
    ) {}
}
