<?php

namespace App\Modules\Employee\Application\CommandHandlers\EmployeeDocument;

use App\Modules\Employee\Application\Commands\EmployeeDocument\ReplaceEmployeeDocumentCommand;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\DocumentDescriptor;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\EmployeeDocumentId;
use App\Modules\Employee\Domain\Exceptions\EmployeeDocumentNotFoundException;
use App\Modules\Employee\Domain\Repositories\EmployeeDocumentRepositoryInterface;
use App\Modules\Identity\Application\Services\AuthorizationService;

class ReplaceEmployeeDocumentHandler
{
    public function __construct(private EmployeeDocumentRepositoryInterface $documents, private AuthorizationService $authorizationService) {}

    public function handle(ReplaceEmployeeDocumentCommand $command, string $userId): void
    {
        $this->authorizationService->requirePermission($userId, 'employee.document.replace');
        $current = $this->documents->findById(EmployeeDocumentId::fromString($command->documentId));
        if (! $current) throw new EmployeeDocumentNotFoundException($command->documentId);

        $replacement = $current->replace(
            EmployeeDocumentId::generate(),
            new DocumentDescriptor($command->filePath, $command->originalName, $command->mime, $command->size),
        );
        $this->documents->save($current);
        $this->documents->save($replacement);
    }
}
