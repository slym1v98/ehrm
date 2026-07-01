<?php

namespace App\Modules\Identity\Application\CommandHandlers;

use App\Modules\Identity\Application\Commands\ChangePasswordCommand;
use App\Modules\Identity\Application\Services\AuthenticationService;

class ChangePasswordHandler
{
    public function __construct(private AuthenticationService $auth) {}

    public function handle(ChangePasswordCommand $command): void
    {
        $this->auth->changePassword($command->user, $command->currentPassword, $command->newPassword);
    }
}
