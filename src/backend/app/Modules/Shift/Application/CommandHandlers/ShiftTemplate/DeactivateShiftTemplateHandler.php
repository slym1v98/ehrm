<?php

namespace App\Modules\Shift\Application\CommandHandlers\ShiftTemplate;

use App\Modules\Identity\Application\Services\AuthorizationService;
use App\Modules\Shift\Application\Commands\ShiftTemplate\DeactivateShiftTemplateCommand;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use App\Modules\Shift\Domain\Exceptions\ShiftTemplateNotFoundException;
use App\Modules\Shift\Domain\Repositories\ShiftTemplateRepositoryInterface;

class DeactivateShiftTemplateHandler
{
    public function __construct(private ShiftTemplateRepositoryInterface $templates, private AuthorizationService $authorizationService) {}

    public function handle(DeactivateShiftTemplateCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'shift.template.update');
        $template = $this->templates->findById(ShiftTemplateId::fromString($command->id));
        if (!$template) throw new ShiftTemplateNotFoundException($command->id);
        $template->deactivate();
        $this->templates->saveAndDispatch($template);
    }
}
