<?php

namespace App\Modules\Employee\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class ContractNotFoundException extends AppException
{
    public function __construct(string $id = '')
    {
        parent::__construct('CONTRACT_NOT_FOUND', "Contract not found: {$id}");
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}
