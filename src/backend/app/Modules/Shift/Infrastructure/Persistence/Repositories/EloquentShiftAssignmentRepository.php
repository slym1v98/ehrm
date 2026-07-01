<?php

namespace App\Modules\Shift\Infrastructure\Persistence\Repositories;

use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\RecurrenceRule;
use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignment;
use App\Modules\Shift\Domain\Aggregates\ShiftAssignment\ShiftAssignmentId;
use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use App\Modules\Shift\Domain\Repositories\ShiftAssignmentRepositoryInterface;
use App\Modules\Shift\Infrastructure\Persistence\Eloquent\ShiftAssignmentModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\Event;

class EloquentShiftAssignmentRepository implements ShiftAssignmentRepositoryInterface
{
    public function __construct(private ShiftAssignmentModel $model) {}

    public function findById(ShiftAssignmentId $id): ?ShiftAssignment
    {
        $record = $this->model->find($id->value);
        return $record ? $this->toDomain($record) : null;
    }

    public function findByEmployeeId(string $employeeId): array
    {
        return $this->model->where('assignable_type', 'employee')->where('assignable_id', $employeeId)
            ->get()->map(fn($r) => $this->toDomain($r))->all();
    }

    public function findByDepartmentId(string $departmentId): array
    {
        return $this->model->where('assignable_type', 'department')->where('assignable_id', $departmentId)
            ->get()->map(fn($r) => $this->toDomain($r))->all();
    }

    public function findActiveByEntity(string $entityType, string $entityId, DateTimeImmutable $date): array
    {
        return $this->model
            ->where('assignable_type', $entityType)
            ->where('assignable_id', $entityId)
            ->where('active', true)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date->format('Y-m-d'));
            })
            ->where('effective_from', '<=', $date->format('Y-m-d'))
            ->get()->map(fn($r) => $this->toDomain($r))->all();
    }

    public function findAllPaginated(int $page, int $perPage = 15): array
    {
        return $this->model->query()->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)->items();
    }

    public function saveAndDispatch(ShiftAssignment $assignment): void
    {
        $this->save($assignment);
        foreach ($assignment->releaseEvents() as $event) {
            Event::dispatch($event);
        }
    }

    private function save(ShiftAssignment $assignment): void
    {
        $this->model->updateOrCreate(
            ['id' => $assignment->id()->value],
            [
                'shift_template_id' => $assignment->shiftTemplateId()->value,
                'assignable_type' => $assignment->assignableType(),
                'assignable_id' => $assignment->assignableId(),
                'effective_from' => $assignment->effectiveFrom()->format('Y-m-d'),
                'effective_to' => $assignment->effectiveTo()?->format('Y-m-d'),
                'recurrence_rule' => $assignment->recurrenceRule(),
                'active' => $assignment->active(),
            ]
        );
    }

    private function toDomain(ShiftAssignmentModel $record): ShiftAssignment
    {
        $rec = $record->recurrence_rule
            ? new RecurrenceRule(
                $record->recurrence_rule['frequency'] ?? 'weekly',
                (int) ($record->recurrence_rule['interval'] ?? 1),
                $record->recurrence_rule['daysOfWeek'] ?? [],
                $record->recurrence_rule['rotationGroup'] ?? null,
            )
            : null;

        return ShiftAssignment::reconstitute(
            ShiftAssignmentId::fromString($record->id),
            ShiftTemplateId::fromString($record->shift_template_id),
            $record->assignable_type,
            $record->assignable_id,
            new DateTimeImmutable($record->effective_from->format('Y-m-d')),
            $record->effective_to ? new DateTimeImmutable($record->effective_to->format('Y-m-d')) : null,
            $rec,
            (bool) $record->active,
        );
    }
}
