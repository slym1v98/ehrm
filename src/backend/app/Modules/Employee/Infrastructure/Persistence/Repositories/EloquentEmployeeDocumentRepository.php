<?php

namespace App\Modules\Employee\Infrastructure\Persistence\Repositories;

use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\DocumentDescriptor;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\DocumentStatus;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\EmployeeDocument;
use App\Modules\Employee\Domain\Aggregates\EmployeeDocument\EmployeeDocumentId;
use App\Modules\Employee\Domain\Repositories\EmployeeDocumentRepositoryInterface;
use App\Modules\Employee\Infrastructure\Persistence\Eloquent\EmployeeDocumentModel;

class EloquentEmployeeDocumentRepository implements EmployeeDocumentRepositoryInterface
{
    public function __construct(private EmployeeDocumentModel $model) {}

    public function findById(EmployeeDocumentId $id): ?EmployeeDocument
    {
        $record = $this->model->find($id->value);
        return $record ? $this->toDomain($record) : null;
    }

    /** @return EmployeeDocument[] */
    public function findByEmployeeId(EmployeeId $employeeId): array
    {
        return $this->model->where('employee_id', $employeeId->value)
            ->get()
            ->map(fn($r) => $this->toDomain($r))
            ->all();
    }

    public function findAllPaginated(int $page, int $perPage = 15, ?EmployeeId $employeeId = null): array
    {
        $q = $this->model->query();
        if ($employeeId) {
            $q->where('employee_id', $employeeId->value);
        }
        return $q->paginate($perPage, ['*'], 'page', $page)->items();
    }

    public function save(EmployeeDocument $document): void
    {
        $this->model->updateOrCreate(
            ['id' => $document->id()->value],
            [
                'employee_id' => $document->employeeId()->value,
                'document_type' => $document->documentType(),
                'category' => $document->category(),
                'file_path' => $document->descriptor()->path,
                'file_original_name' => $document->descriptor()->originalName,
                'file_mime' => $document->descriptor()->mime,
                'file_size' => $document->descriptor()->size,
                'issue_date' => $document->issueDate()?->format('Y-m-d'),
                'expiry_date' => $document->expiryDate()?->format('Y-m-d'),
                'status' => $document->status()->value,
            ]
        );
    }

    private function toDomain(EmployeeDocumentModel $record): EmployeeDocument
    {
        return EmployeeDocument::reconstitute(
            EmployeeDocumentId::fromString($record->id),
            EmployeeId::fromString($record->employee_id),
            $record->document_type,
            $record->category,
            new DocumentDescriptor($record->file_path, $record->file_original_name, $record->file_mime, (int) $record->file_size),
            $record->issue_date ? new \DateTimeImmutable($record->issue_date->format('Y-m-d')) : null,
            $record->expiry_date ? new \DateTimeImmutable($record->expiry_date->format('Y-m-d')) : null,
            DocumentStatus::from($record->status),
        );
    }
}
