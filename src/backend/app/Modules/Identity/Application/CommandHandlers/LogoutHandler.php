<?php

namespace App\Modules\Identity\Application\CommandHandlers;

use App\Modules\Identity\Application\Commands\LogoutCommand;
use App\Modules\Identity\Application\Services\AuthenticationService;

class LogoutHandler
{
    public function __construct(private AuthenticationService $auth) {}

    public function handle(LogoutCommand $command): void
    {
        $this->auth->logout($command->user);
    }
}
