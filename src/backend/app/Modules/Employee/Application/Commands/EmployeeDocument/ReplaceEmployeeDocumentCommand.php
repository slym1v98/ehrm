<?php

namespace App\Modules\Employee\Application\Commands\EmployeeDocument;

final readonly class ReplaceEmployeeDocumentCommand
{
    public function __construct(public string $documentId, public string $filePath, public string $originalName, public string $mime, public int $size) {}
}
