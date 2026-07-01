<?php

namespace App\Modules\Shift\Domain\Repositories;

use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplate;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;

interface ShiftTemplateRepositoryInterface
{
    public function findById(ShiftTemplateId $id): ?ShiftTemplate;
    public function findByCode(string $code): ?ShiftTemplate;
    public function existsByCode(string $code): bool;
    /** @return ShiftTemplate[] */
    public function findAllPaginated(int $page, int $perPage = 15): array;
    public function saveAndDispatch(ShiftTemplate $template): void;
}
