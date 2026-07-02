<?php

namespace App\Modules\Reporting\Application\Contracts;

interface ReportQueryInterface
{
    public function execute(array $filters, string $requestedBy): array;
}
