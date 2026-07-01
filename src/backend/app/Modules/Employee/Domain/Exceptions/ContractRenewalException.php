<?php

namespace App\Modules\Employee\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class ContractRenewalException extends AppException
{
    public function __construct(string $message = 'Invalid contract renewal.')
    {
        parent::__construct('CONTRACT_RENEWAL_INVALID', $message);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}
