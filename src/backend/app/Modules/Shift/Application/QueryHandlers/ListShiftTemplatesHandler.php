<?php

namespace App\Modules\Shift\Application\QueryHandlers;

use App\Modules\Shift\Application\Queries\ListShiftTemplatesQuery;
use App\Modules\Shift\Domain\Repositories\ShiftTemplateRepositoryInterface;

class ListShiftTemplatesHandler
{
    public function __construct(private ShiftTemplateRepositoryInterface $templates) {}

    public function handle(ListShiftTemplatesQuery $query): array
    {
        return $this->templates->findAllPaginated($query->page, $query->perPage);
    }
}
