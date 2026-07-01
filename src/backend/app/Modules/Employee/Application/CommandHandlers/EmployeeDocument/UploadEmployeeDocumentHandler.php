<?php

namespace App\Modules\Employee\Application\CommandHandlers\EmployeeDocument;

use App\Modules\Employee\Application\Commands\EmployeeDocument\UploadEmployeeDocumentCommand;
use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\DocumentDescriptor;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\EmployeeDocument;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\EmployeeDocumentId;
use App\Modules\Employee\Domain\Exceptions\EmployeeNotFoundException;
use App\Modules\Employee\Domain\Repositories\EmployeeDocumentRepositoryInterface;
use App\Modules\Employee\Domain\Repositories\EmployeeRepositoryInterface;
use App\Modules\Identity\Application\Services\AuthorizationService;

class UploadEmployeeDocumentHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private EmployeeDocumentRepositoryInterface $documents,
        private AuthorizationService $authorizationService,
    ) {}

    public function handle(UploadEmployeeDocumentCommand $command, string $userId): EmployeeDocument
    {
        $this->authorizationService->requirePermission($userId, 'employee.document.upload');
        $employee = $this->employees->findById(EmployeeId::fromString($command->employeeId));
        if (! $employee) throw new EmployeeNotFoundException($command->employeeId);

        $document = EmployeeDocument::upload(
            EmployeeDocumentId::generate(),
            EmployeeId::fromString($command->employeeId),
            $command->documentType,
            new DocumentDescriptor($command->filePath, $command->originalName, $command->mime, $command->size),
            $command->category,
        );

        $this->documents->save($document);
        return $document;
    }
}
