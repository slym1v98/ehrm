<?php

namespace App\Modules\Shift\Application\Queries;

final readonly class GetShiftTemplateQuery
{
    public function __construct(public string $id) {}
}
