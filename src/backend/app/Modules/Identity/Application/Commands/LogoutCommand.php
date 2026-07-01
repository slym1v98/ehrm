<?php

namespace App\Modules\Identity\Application\Commands;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\UserModel;

final readonly class LogoutCommand
{
    public function __construct(public UserModel $user) {}
}
