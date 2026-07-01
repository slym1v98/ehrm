<?php

namespace App\Modules\Employee\Application\CommandHandlers\EmployeeDocument;

use App\Modules\Employee\Application\Commands\EmployeeDocument\ArchiveEmployeeDocumentCommand;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\EmployeeDocumentId;
use App\Modules\Employee\Domain\Exceptions\EmployeeDocumentNotFoundException;
use App\Modules\Employee\Domain\Repositories\EmployeeDocumentRepositoryInterface;
use App\Modules\Identity\Application\Services\AuthorizationService;

class ArchiveEmployeeDocumentHandler
{
    public function __construct(private EmployeeDocumentRepositoryInterface $documents, private AuthorizationService $authorizationService) {}

    public function handle(ArchiveEmployeeDocumentCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'employee.document.archive');
        $document = $this->documents->findById(EmployeeDocumentId::fromString($command->documentId));
        if (! $document) throw new EmployeeDocumentNotFoundException($command->documentId);
        $document->archive();
        $this->documents->save($document);
    }
}
