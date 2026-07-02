<?php

namespace App\Modules\Workflow\Domain\ValueObjects;

enum WorkflowActionType: string
{
    case APPROVE = 'approve';
    case REJECT = 'reject';
    case RETURN_FOR_EDIT = 'return_for_edit';
    case CANCEL = 'cancel';
}
