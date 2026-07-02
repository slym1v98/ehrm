<?php

namespace App\Modules\Workflow\Domain\ValueObjects;

enum AssigneeType: string
{
    case ROLE = 'role';
    case DEPARTMENT = 'department';
    case SPECIFIC_USER = 'specific_user';
}
