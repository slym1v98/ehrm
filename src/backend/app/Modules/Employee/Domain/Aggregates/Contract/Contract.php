<?php

namespace App\Modules\Employee\Domain\Aggregates\Contract;

use App\Modules\Employee\Domain\Aggregates\Employee\EmployeeId;
use App\Modules\Employee\Domain\Events\ContractActivated;
use App\Modules\Employee\Domain\Events\ContractCreated;
use App\Modules\Employee\Domain\Events\ContractExpired;
use App\Modules\Employee\Domain\Events\ContractRenewed;
use App\Modules\Employee\Domain\Events\ContractTerminated;
use App\Modules\Employee\Domain\Exceptions\ContractRenewalException;
use DateTimeImmutable;

final class Contract
{
    /** @var object[] */
    private array $recordedEvents = [];

    private function __construct(
        private readonly ContractId $id,
        private readonly EmployeeId $employeeId,
        private readonly string $contractNumber,
        private ContractTerm $term,
        private ContractStatus $status,
        private ?ContractId $predecessorContractId = null,
        private ?DateTimeImmutable $signDate = null,
        private ?string $positionId = null,
    ) {}

    public static function create(ContractId $id, EmployeeId $employeeId, string $contractNumber, ContractTerm $term, ?string $positionId = null): self
    {
        if (in_array($term->type, ['definite', 'seasonal'], true) && $term->dateRange->end === null) {
            throw new ContractRenewalException('Definite/seasonal contracts require end_date.');
        }

        $contract = new self($id, $employeeId, $contractNumber, $term, ContractStatus::Draft, null, null, $positionId);
        $contract->record(new ContractCreated($id, $employeeId, $term->type, $contractNumber, new DateTimeImmutable()));
        return $contract;
    }

    public static function reconstitute(
        ContractId $id,
        EmployeeId $employeeId,
        string $contractNumber,
        ContractTerm $term,
        ContractStatus $status,
        ?ContractId $predecessorContractId = null,
        ?DateTimeImmutable $signDate = null,
        ?string $positionId = null,
    ): self {
        return new self($id, $employeeId, $contractNumber, $term, $status, $predecessorContractId, $signDate, $positionId);
    }

    public function activate(): void
    {
        if ($this->status === ContractStatus::Active) {
            return;
        }
        $this->status = ContractStatus::Active;
        $this->record(new ContractActivated($this->id, $this->employeeId, new DateTimeImmutable()));
    }

    public function renew(ContractId $newId, string $newNumber, ContractTerm $term): self
    {
        $contract = new self($newId, $this->employeeId, $newNumber, $term, ContractStatus::Draft, $this->id, null, $this->positionId);
        $contract->record(new ContractRenewed($newId, $this->id, $this->employeeId, new DateTimeImmutable()));
        return $contract;
    }

    public function terminate(): void
    {
        $this->status = ContractStatus::Terminated;
        $this->record(new ContractTerminated($this->id, $this->employeeId, new DateTimeImmutable()));
    }

    public function cancel(): void
    {
        $this->status = ContractStatus::Cancelled;
        $this->record(new ContractTerminated($this->id, $this->employeeId, new DateTimeImmutable()));
    }

    public function markExpired(): void
    {
        $this->status = ContractStatus::Expired;
        $this->record(new ContractExpired($this->id, $this->employeeId, new DateTimeImmutable()));
    }

    public function term(): DateRange
    {
        return $this->term->dateRange;
    }

    public function id(): ContractId { return $this->id; }
    public function employeeId(): EmployeeId { return $this->employeeId; }
    public function contractNumber(): string { return $this->contractNumber; }
    public function contractType(): string { return $this->term->type; }
    public function status(): ContractStatus { return $this->status; }
    public function predecessorContractId(): ?ContractId { return $this->predecessorContractId; }
    public function signDate(): ?DateTimeImmutable { return $this->signDate; }
    public function positionId(): ?string { return $this->positionId; }
    public function baseSalary(): ?float { return $this->term->salary; }

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
