<?php

namespace App\Modules\Shift\Application\CommandHandlers\ShiftAssignment;

use App\Modules\Identity\Application\Services\AuthorizationService;
use App\Modules\Shift\Application\Commands\ShiftAssignment\AssignShiftCommand;
use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\RecurrenceRule;
use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignment;
use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignmentId;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use App\Modules\Shift\Domain\Exceptions\ShiftTemplateNotFoundException;
use App\Modules\Shift\Domain\Repositories\ShiftAssignmentRepositoryInterface;
use App\Modules\Shift\Domain\Repositories\ShiftTemplateRepositoryInterface;
use DateTimeImmutable;

class AssignShiftHandler
{
    public function __construct(
        private ShiftTemplateRepositoryInterface $templates,
        private ShiftAssignmentRepositoryInterface $assignments,
        private AuthorizationService $authorizationService,
    ) {}

    public function handle(AssignShiftCommand $command, string $userId): ShiftAssignment
    {
        $this->authorizationService->requirePermission($userId, 'shift.template.update');

        $template = $this->templates->findById(ShiftTemplateId::fromString($command->shiftTemplateId));
        if (!$template) throw new ShiftTemplateNotFoundException($command->shiftTemplateId);

        $assignment = ShiftAssignment::assign(
            ShiftAssignmentId::generate(),
            ShiftTemplateId::fromString($command->shiftTemplateId),
            $command->assignableType,
            $command->assignableId,
            new DateTimeImmutable($command->effectiveFrom),
            $command->effectiveTo ? new DateTimeImmutable($command->effectiveTo) : null,
            $command->recurrenceRule ? new RecurrenceRule(
                $command->recurrenceRule['frequency'] ?? 'weekly',
                (int) ($command->recurrenceRule['interval'] ?? 1),
                $command->recurrenceRule['daysOfWeek'] ?? [],
                $command->recurrenceRule['rotationGroup'] ?? null,
            ) : null,
        );

        $this->assignments->saveAndDispatch($assignment);
        return $assignment;
    }
}
