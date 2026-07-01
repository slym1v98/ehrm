<?php

namespace App\Modules\Identity\Application\Commands;

final readonly class LoginCommand
{
    public function __construct(public string $email, public string $password) {}
}
