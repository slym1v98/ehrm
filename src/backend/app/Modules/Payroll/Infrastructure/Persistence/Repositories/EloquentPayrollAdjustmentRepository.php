<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Repositories;

use App\Modules\Payroll\Domain\Aggregates\PayrollAdjustment\{PayrollAdjustment, PayrollAdjustmentId};
use App\Modules\Payroll\Domain\Aggregates\PayrollEntry\PayrollEntryId;
use App\Modules\Payroll\Domain\Repositories\PayrollAdjustmentRepositoryInterface;
use App\Modules\Payroll\Domain\ValueObjects\{AdjustmentStatus, Money};
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\PayrollAdjustmentModel;
use DateTimeImmutable;
use ReflectionClass;

class EloquentPayrollAdjustmentRepository implements PayrollAdjustmentRepositoryInterface
{
    public function save(PayrollAdjustment $adj): void
    {
        PayrollAdjustmentModel::updateOrCreate(
            ['id' => $adj->getId()->value],
            [
                'entry_id' => $adj->getEntryId()->value,
                'component_id' => $adj->getComponentId(),
                'adjustment_type' => $adj->getAdjustmentType(),
                'amount' => $adj->getAmount()->toDecimal(),
                'reason' => $adj->getReason(),
                'status' => $adj->getStatus()->value,
                'submitted_by' => $adj->getSubmittedBy(),
                'submitted_at' => $adj->getSubmittedAt()->format('Y-m-d H:i:s'),
                'approved_by' => $adj->getApprovedBy(),
                'approved_at' => $adj->getApprovedAt()?->format('Y-m-d H:i:s'),
                'rejected_reason' => $adj->getRejectedReason(),
            ]
        );
    }

    public function findById(PayrollAdjustmentId $id): ?PayrollAdjustment
    {
        $m = PayrollAdjustmentModel::find($id->value);
        return $m ? $this->toAggregate($m) : null;
    }

    public function findByEntry(PayrollEntryId $entryId): array
    {
        return PayrollAdjustmentModel::where('entry_id', $entryId->value)->get()
            ->map(fn($m) => $this->toAggregate($m))->all();
    }

    private function toAggregate(PayrollAdjustmentModel $m): PayrollAdjustment
    {
        $ref = new ReflectionClass(PayrollAdjustment::class);
        $a = $ref->newInstanceWithoutConstructor();
        $props = [
            'id' => PayrollAdjustmentId::fromString($m->id),
            'entryId' => PayrollEntryId::fromString($m->entry_id),
            'componentId' => $m->component_id,
            'adjustmentType' => $m->adjustment_type,
            'amount' => Money::fromDecimal((float)$m->amount),
            'reason' => $m->reason,
            'status' => AdjustmentStatus::from($m->status),
            'submittedBy' => $m->submitted_by,
            'submittedAt' => new DateTimeImmutable($m->submitted_at->format('Y-m-d H:i:s')),
            'approvedBy' => $m->approved_by,
            'approvedAt' => $m->approved_at ? new DateTimeImmutable($m->approved_at->format('Y-m-d H:i:s')) : null,
            'rejectedReason' => $m->rejected_reason,
        ];
        foreach ($props as $n => $v) $ref->getProperty($n)->setValue($a, $v);
        return $a;
    }
}
