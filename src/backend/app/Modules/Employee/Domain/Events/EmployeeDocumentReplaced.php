<?php

namespace App\Modules\Employee\Domain\Events;

use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\EmployeeDocumentId;
use DateTimeImmutable;

final readonly class EmployeeDocumentReplaced
{
    public function __construct(public EmployeeDocumentId $documentId, public EmployeeId $employeeId, public string $replacementPath, public string $previousPath, public DateTimeImmutable $occurredAt) {}
}
