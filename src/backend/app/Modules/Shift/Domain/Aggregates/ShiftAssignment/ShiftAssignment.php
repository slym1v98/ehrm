<?php

namespace App\Modules\Shift\Domain\Aggregates\ShiftAssignment;

use App\Modules\Shift\Domain\Aggregates\ShiftTemplate\ShiftTemplateId;
use App\Modules\Shift\Domain\Events\ShiftAssigned;
use App\Modules\Shift\Domain\Events\ShiftAssignmentChanged;
use App\Modules\Shift\Domain\Events\ShiftAssignmentEnded;
use DateTimeImmutable;

final class ShiftAssignment
{
    /** @var object[] */
    private array $recordedEvents = [];

    private function __construct(
        private readonly ShiftAssignmentId $id,
        private ShiftTemplateId $shiftTemplateId,
        private readonly string $assignableType,
        private readonly string $assignableId,
        private DateTimeImmutable $effectiveFrom,
        private ?DateTimeImmutable $effectiveTo,
        private ?RecurrenceRule $recurrenceRule,
        private bool $active,
    ) {}

    public static function assign(
        ShiftAssignmentId $id,
        ShiftTemplateId $shiftTemplateId,
        string $assignableType,
        string $assignableId,
        DateTimeImmutable $effectiveFrom,
        ?DateTimeImmutable $effectiveTo,
        ?RecurrenceRule $recurrenceRule,
    ): self {
        $assignment = new self($id, $shiftTemplateId, $assignableType, $assignableId, $effectiveFrom, $effectiveTo, $recurrenceRule, true);
        $assignment->record(new ShiftAssigned($id, $shiftTemplateId, $assignableType, $assignableId, $effectiveFrom->format('Y-m-d'), new DateTimeImmutable()));
        return $assignment;
    }

    public static function reconstitute(
        ShiftAssignmentId $id,
        ShiftTemplateId $shiftTemplateId,
        string $assignableType,
        string $assignableId,
        DateTimeImmutable $effectiveFrom,
        ?DateTimeImmutable $effectiveTo,
        ?RecurrenceRule $recurrenceRule,
        bool $active,
    ): self {
        return new self($id, $shiftTemplateId, $assignableType, $assignableId, $effectiveFrom, $effectiveTo, $recurrenceRule, $active);
    }

    public function endAssignment(DateTimeImmutable $effectiveTo): void
    {
        $this->effectiveTo = $effectiveTo;
        $this->active = false;
        $this->record(new ShiftAssignmentEnded($this->id, $effectiveTo->format('Y-m-d'), new DateTimeImmutable()));
    }

    public function changeTemplate(ShiftTemplateId $newTemplateId, DateTimeImmutable $effectiveFrom): void
    {
        $old = $this->shiftTemplateId;
        $this->shiftTemplateId = $newTemplateId;
        $this->effectiveFrom = $effectiveFrom;
        $this->record(new ShiftAssignmentChanged($this->id, $old, $newTemplateId, new DateTimeImmutable()));
    }

    public function id(): ShiftAssignmentId { return $this->id; }
    public function shiftTemplateId(): ShiftTemplateId { return $this->shiftTemplateId; }
    public function assignableType(): string { return $this->assignableType; }
    public function assignableId(): string { return $this->assignableId; }
    public function effectiveFrom(): DateTimeImmutable { return $this->effectiveFrom; }
    public function effectiveTo(): ?DateTimeImmutable { return $this->effectiveTo; }
    public function recurrenceRule(): ?RecurrenceRule { return $this->recurrenceRule; }
    public function active(): bool { return $this->active; }

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
