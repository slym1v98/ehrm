<?php

namespace App\Modules\Shift\Application\Queries;

final readonly class ListShiftTemplatesQuery
{
    public function __construct(public int $page = 1, public int $perPage = 15) {}
}
