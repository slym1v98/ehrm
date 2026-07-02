<?php

namespace App\Modules\Reporting\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class ReportDefinitionNotFoundException extends AppException
{
    public function __construct(string $detail = '')
    {
        parent::__construct('REPORT_DEFINITION_NOT_FOUND', trim('Report definition not found: '.$detail));
    }
    public function getHttpStatus(): int { return 404; }
}
