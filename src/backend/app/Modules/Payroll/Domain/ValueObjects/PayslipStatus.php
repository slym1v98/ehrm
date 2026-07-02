<?php

namespace App\Modules\Payroll\Domain\ValueObjects;

enum PayslipStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
