<?php

namespace App\Modules\Employee\Domain\Aggregates\EmployeeDocument;

use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Events\EmployeeDocumentArchived;
use App\Modules\Employee\Domain\Events\EmployeeDocumentExpired;
use App\Modules\Employee\Domain\Events\EmployeeDocumentReplaced;
use App\Modules\Employee\Domain\Events\EmployeeDocumentUploaded;
use DateTimeImmutable;

final class EmployeeDocument
{
    /** @var object[] */
    private array $recordedEvents = [];

    private function __construct(
        private readonly EmployeeDocumentId $id,
        private readonly EmployeeId $employeeId,
        private readonly string $documentType,
        private readonly ?string $category,
        private DocumentDescriptor $descriptor,
        private readonly ?DateTimeImmutable $issueDate,
        private readonly ?DateTimeImmutable $expiryDate,
        private DocumentStatus $status,
    ) {}

    public static function upload(EmployeeDocumentId $id, EmployeeId $employeeId, string $documentType, DocumentDescriptor $descriptor, ?string $category = null, ?DateTimeImmutable $issueDate = null, ?DateTimeImmutable $expiryDate = null): self
    {
        $document = new self($id, $employeeId, $documentType, $category, $descriptor, $issueDate, $expiryDate, DocumentStatus::Active);
        $document->record(new EmployeeDocumentUploaded($id, $employeeId, $documentType, $descriptor->path, new DateTimeImmutable()));
        return $document;
    }

    public static function reconstitute(
        EmployeeDocumentId $id,
        EmployeeId $employeeId,
        string $documentType,
        ?string $category,
        DocumentDescriptor $descriptor,
        ?DateTimeImmutable $issueDate,
        ?DateTimeImmutable $expiryDate,
        DocumentStatus $status,
    ): self {
        return new self($id, $employeeId, $documentType, $category, $descriptor, $issueDate, $expiryDate, $status);
    }

    public function replace(EmployeeDocumentId $newId, DocumentDescriptor $newDescriptor): self
    {
        $this->status = DocumentStatus::Archived;
        $replacement = new self($newId, $this->employeeId, $this->documentType, $this->category, $newDescriptor, $this->issueDate, $this->expiryDate, DocumentStatus::Active);
        $replacement->record(new EmployeeDocumentReplaced($newId, $this->employeeId, $newDescriptor->path, $this->descriptor->path, new DateTimeImmutable()));
        return $replacement;
    }

    public function archive(): void
    {
        $this->status = DocumentStatus::Archived;
        $this->record(new EmployeeDocumentArchived($this->id, $this->employeeId, new DateTimeImmutable()));
    }

    public function markExpired(): void
    {
        $this->status = DocumentStatus::Expired;
        $this->record(new EmployeeDocumentExpired($this->id, $this->employeeId, new DateTimeImmutable()));
    }

    public function status(): DocumentStatus
    {
        return $this->status;
    }

    public function id(): EmployeeDocumentId { return $this->id; }
    public function employeeId(): EmployeeId { return $this->employeeId; }
    public function documentType(): string { return $this->documentType; }
    public function category(): ?string { return $this->category; }
    public function descriptor(): DocumentDescriptor { return $this->descriptor; }
    public function issueDate(): ?DateTimeImmutable { return $this->issueDate; }
    public function expiryDate(): ?DateTimeImmutable { return $this->expiryDate; }

    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];
        return $events;
    }

    private function record(object $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
