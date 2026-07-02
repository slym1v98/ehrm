<?php

namespace App\Modules\Payroll\Domain\ValueObjects;

enum RunStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
