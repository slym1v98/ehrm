<?php

namespace App\Modules\Shift\Application\CommandHandlers\ShiftAssignment;

use App\Modules\Identity\Application\Services\AuthorizationService;
use App\Modules\Shift\Application\Commands\ShiftAssignment\ChangeShiftAssignmentCommand;
use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignmentId;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use App\Modules\Shift\Domain\Exceptions\ShiftAssignmentNotFoundException;
use App\Modules\Shift\Domain\Repositories\ShiftAssignmentRepositoryInterface;
use DateTimeImmutable;

class ChangeShiftAssignmentHandler
{
    public function __construct(private ShiftAssignmentRepositoryInterface $assignments, private AuthorizationService $authorizationService) {}

    public function handle(ChangeShiftAssignmentCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'shift.template.update');
        $assignment = $this->assignments->findById(ShiftAssignmentId::fromString($command->id));
        if (!$assignment) throw new ShiftAssignmentNotFoundException($command->id);
        $assignment->changeTemplate(ShiftTemplateId::fromString($command->newTemplateId), new DateTimeImmutable($command->effectiveFrom));
        $this->assignments->saveAndDispatch($assignment);
    }
}
