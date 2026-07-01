<?php

namespace App\Modules\Identity\Domain\Aggregates\User;

enum ScopeType: string
{
    case SelfOnly = 'self';
    case DirectReports = 'direct_reports';
    case Department = 'department';
    case Branch = 'branch';
    case AllCompany = 'all_company';
}
