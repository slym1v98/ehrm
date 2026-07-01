<?php

namespace App\Modules\Employee\Application\Commands\EmployeeDocument;

final readonly class ArchiveEmployeeDocumentCommand
{
    public function __construct(public string $documentId) {}
}
