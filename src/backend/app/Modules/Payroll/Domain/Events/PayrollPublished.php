<?php

namespace App\Modules\Payroll\Domain\Events;

readonly class PayrollPublished
{
    public function __construct(public string $periodId, public string $publishedBy) {}
}
