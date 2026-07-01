<?php

namespace App\Modules\Shift\Application\QueryHandlers;

use App\Modules\Shift\Application\Queries\GetShiftTemplateQuery;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use App\Modules\Shift\Domain\Exceptions\ShiftTemplateNotFoundException;
use App\Modules\Shift\Domain\Repositories\ShiftTemplateRepositoryInterface;

class GetShiftTemplateHandler
{
    public function __construct(private ShiftTemplateRepositoryInterface $templates) {}

    public function handle(GetShiftTemplateQuery $query): mixed
    {
        $template = $this->templates->findById(ShiftTemplateId::fromString($query->id));
        if (!$template) throw new ShiftTemplateNotFoundException($query->id);
        return $template;
    }
}
