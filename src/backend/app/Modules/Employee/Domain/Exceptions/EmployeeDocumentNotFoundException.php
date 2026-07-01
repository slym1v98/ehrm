<?php

namespace App\Modules\Employee\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class EmployeeDocumentNotFoundException extends AppException
{
    public function __construct(string $id = '')
    {
        parent::__construct('EMPLOYEE_DOCUMENT_NOT_FOUND', "Employee document not found: {$id}");
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}
