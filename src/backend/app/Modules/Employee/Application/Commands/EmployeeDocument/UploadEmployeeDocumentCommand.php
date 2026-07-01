<?php

namespace App\Modules\Employee\Application\Commands\EmployeeDocument;

final readonly class UploadEmployeeDocumentCommand
{
    public function __construct(public string $employeeId, public string $documentType, public string $filePath, public string $originalName, public string $mime, public int $size, public ?string $category = null) {}
}
