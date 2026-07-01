<?php

namespace App\Modules\Identity\Application\CommandHandlers;

use App\Modules\Identity\Application\Commands\LoginCommand;
use App\Modules\Identity\Application\Services\AuthenticationService;

class LoginHandler
{
    public function __construct(private AuthenticationService $auth) {}

    public function handle(LoginCommand $command): array
    {
        return $this->auth->login($command->email, $command->password);
    }
}
