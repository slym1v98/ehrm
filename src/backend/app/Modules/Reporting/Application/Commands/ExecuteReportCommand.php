<?php

namespace App\Modules\Reporting\Application\Commands;

readonly class ExecuteReportCommand
{
    public function __construct(public string $code, public string $requestedBy, public array $filters = []) {}
}
