<?php

namespace App\Modules\Reporting\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class ReportRunNotFoundException extends AppException
{
    public function __construct(string $detail = '')
    {
        parent::__construct('REPORT_RUN_NOT_FOUND', trim('Report run not found: '.$detail));
    }
    public function getHttpStatus(): int { return 404; }
}
