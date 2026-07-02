<?php

namespace App\Modules\Payroll\Domain\Aggregates\Payslip;

use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntry;
use App\Modules\Payroll\Domain\Aggregates\PayrollPeriod\PayrollPeriodId;
use App\Modules\Payroll\Domain\ValueObjects\Money;
use App\Modules\Payroll\Domain\ValueObjects\PayslipStatus;
use DateTimeImmutable;

class Payslip
{
    private function __construct(
        private PayslipId $id,
        private string $entryId,
        private string $employeeId,
        private PayrollPeriodId $periodId,
        private Money $gross,
        private Money $deductions,
        private Money $net,
        private array $payload,
        private PayslipStatus $status,
        private ?DateTimeImmutable $publishedAt = null,
        private ?DateTimeImmutable $firstAccessedAt = null,
        private int $accessCount = 0,
    ) {}

    public static function publishFromEntry(PayslipId $id, PayrollEntry $entry): self
    {
        $payload = [
            'employee_id' => $entry->getEmployeeId(),
            'gross_amount' => $entry->getGrossAmount()->toDecimal(),
            'deduction_amount' => $entry->getDeductionAmount()->toDecimal(),
            'net_amount' => $entry->getNetAmount()->toDecimal(),
            'contract_snapshot' => $entry->getContractSnapshot(),
            'attendance_snapshot' => $entry->getAttendanceSnapshot(),
            'leave_snapshot' => $entry->getLeaveSnapshot(),
            'lines' => array_map(fn($line) => [
                'component_id' => $line['component_id'],
                'category' => $line['category'],
                'amount' => $line['amount'] instanceof Money ? $line['amount']->toDecimal() : $line['amount'],
                'note' => $line['note'] ?? null,
            ], $entry->getLines()),
        ];
        return new self(
            id: $id,
            entryId: $entry->getId()->value,
            employeeId: $entry->getEmployeeId(),
            periodId: $entry->getPeriodId(),
            gross: $entry->getGrossAmount(),
            deductions: $entry->getDeductionAmount(),
            net: $entry->getNetAmount(),
            payload: $payload,
            status: PayslipStatus::Published,
            publishedAt: new DateTimeImmutable(),
        );
    }

    public function recordAccess(): void
    {
        if ($this->firstAccessedAt === null) {
            $this->firstAccessedAt = new DateTimeImmutable();
        }
        $this->accessCount++;
    }

    public function getId(): PayslipId { return $this->id; }
    public function getEntryId(): string { return $this->entryId; }
    public function getEmployeeId(): string { return $this->employeeId; }
    public function getPeriodId(): PayrollPeriodId { return $this->periodId; }
    public function getGross(): Money { return $this->gross; }
    public function getDeductions(): Money { return $this->deductions; }
    public function getNet(): Money { return $this->net; }
    public function getPayload(): array { return $this->payload; }
    public function getStatus(): PayslipStatus { return $this->status; }
    public function getPublishedAt(): ?DateTimeImmutable { return $this->publishedAt; }
    public function getFirstAccessedAt(): ?DateTimeImmutable { return $this->firstAccessedAt; }
    public function getAccessCount(): int { return $this->accessCount; }
}
