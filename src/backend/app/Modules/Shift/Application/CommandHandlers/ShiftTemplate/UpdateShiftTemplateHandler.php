<?php

namespace App\Modules\Shift\Application\CommandHandlers\ShiftTemplate;

use App\Modules\Identity\Application\Services\AuthorizationService;
use App\Modules\Shift\Application\Commands\ShiftTemplate\UpdateShiftTemplateCommand;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\FlexibilityRules;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\OvertimeRules;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftWindow;
use App\Modules\Shift\Domain\Exceptions\ShiftTemplateNotFoundException;
use App\Modules\Shift\Domain\Repositories\ShiftTemplateRepositoryInterface;

class UpdateShiftTemplateHandler
{
    public function __construct(private ShiftTemplateRepositoryInterface $templates, private AuthorizationService $authorizationService) {}

    public function handle(UpdateShiftTemplateCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'shift.template.update');
        $template = $this->templates->findById(ShiftTemplateId::fromString($command->id));
        if (!$template) throw new ShiftTemplateNotFoundException($command->id);

        $template->updateDetails(
            $command->name,
            ShiftWindow::fromStrings($command->startTime, $command->endTime),
            $command->breakMinutes,
            $command->lateToleranceMinutes,
            $command->overtimeRules ? new OvertimeRules(...$command->overtimeRules) : new OvertimeRules(0, 15, 0, 0, 0),
            $command->flexibilityRules ? new FlexibilityRules(...$command->flexibilityRules) : new FlexibilityRules(0, 0, null, null),
            $command->payrollAttributionRule,
        );
        $this->templates->saveAndDispatch($template);
    }
}
