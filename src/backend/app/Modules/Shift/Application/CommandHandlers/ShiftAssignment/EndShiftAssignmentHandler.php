<?php

namespace App\Modules\Shift\Application\CommandHandlers\ShiftAssignment;

use App\Modules\Identity\Application\Services\AuthorizationService;
use App\Modules\Shift\Application\Commands\ShiftAssignment\EndShiftAssignmentCommand;
use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignmentId;
use App\Modules\Shift\Domain\Exceptions\ShiftAssignmentNotFoundException;
use App\Modules\Shift\Domain\Repositories\ShiftAssignmentRepositoryInterface;
use DateTimeImmutable;

class EndShiftAssignmentHandler
{
    public function __construct(private ShiftAssignmentRepositoryInterface $assignments, private AuthorizationService $authorizationService) {}

    public function handle(EndShiftAssignmentCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'shift.template.update');
        $assignment = $this->assignments->findById(ShiftAssignmentId::fromString($command->id));
        if (!$assignment) throw new ShiftAssignmentNotFoundException($command->id);
        $assignment->endAssignment(new DateTimeImmutable($command->effectiveTo));
        $this->assignments->saveAndDispatch($assignment);
    }
}
