<?php

namespace App\Modules\Employee\Application\Queries;

final readonly class ListEmployeesQuery
{
    public function __construct(public int $page = 1, public int $perPage = 15) {}
}
