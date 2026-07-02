<?php

namespace App\Modules\Notification\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class NotificationMessageNotFoundException extends AppException
{
    public function __construct(string $detail = '')
    {
        parent::__construct('NOTIFICATION_MESSAGE_NOT_FOUND', trim('Notification message not found: '.$detail));
    }

    public function getHttpStatus(): int { return 404; }
}
