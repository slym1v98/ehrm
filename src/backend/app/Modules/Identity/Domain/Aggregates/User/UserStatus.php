<?php

namespace App\Modules\Identity\Domain\Aggregates\User;

enum UserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
