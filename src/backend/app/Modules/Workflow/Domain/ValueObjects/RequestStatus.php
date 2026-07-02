<?php

namespace App\Modules\Workflow\Domain\ValueObjects;

enum RequestStatus: string
{
    case PENDING = 'pending';
    case IN_REVIEW = 'in_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case RETURNED = 'returned';
}
