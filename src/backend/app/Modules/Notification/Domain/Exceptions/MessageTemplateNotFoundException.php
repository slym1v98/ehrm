<?php

namespace App\Modules\Notification\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class MessageTemplateNotFoundException extends AppException
{
    public function __construct(string $detail = '')
    {
        parent::__construct('NOTIFICATION_TEMPLATE_NOT_FOUND', trim('Message template not found: '.$detail));
    }

    public function getHttpStatus(): int { return 404; }
}
