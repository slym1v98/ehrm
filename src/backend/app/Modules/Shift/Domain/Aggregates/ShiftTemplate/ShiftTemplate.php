<?php

namespace App\Modules\Shift\Domain\Aggregates\ShiftTemplate;

use App\Modules\Shift\Domain\Events\ShiftTemplateActivated;
use App\Modules\Shift\Domain\Events\ShiftTemplateCreated;
use App\Modules\Shift\Domain\Events\ShiftTemplateDeactivated;
use App\Modules\Shift\Domain\Events\ShiftTemplateUpdated;
use App\Modules\Shift\Domain\Exceptions\InvalidShiftTemplateException;
use DateTimeImmutable;

final class ShiftTemplate
{
    /** @var object[] */
    private array $recordedEvents = [];

    private function __construct(
        private readonly ShiftTemplateId $id,
        private readonly string $code,
        private string $name,
        private ShiftWindow $shiftWindow,
        private int $breakMinutes,
        private int $lateToleranceMinutes,
        private OvertimeRules $overtimeRules,
        private FlexibilityRules $flexibilityRules,
        private ?string $payrollAttributionRule,
        private bool $active,
    ) {}

    public static function create(
        ShiftTemplateId $id,
        string $code,
        string $name,
        ShiftWindow $shiftWindow,
        int $breakMinutes,
        int $lateToleranceMinutes,
        OvertimeRules $overtimeRules,
        FlexibilityRules $flexibilityRules,
        ?string $payrollAttributionRule,
    ): self {
        self::validate($shiftWindow, $breakMinutes, $payrollAttributionRule);

        $template = new self(
            $id,
            strtoupper(trim($code)),
            trim($name),
            $shiftWindow,
            $breakMinutes,
            $lateToleranceMinutes,
            $overtimeRules,
            $flexibilityRules,
            $payrollAttributionRule,
            true,
        );

        $template->record(new ShiftTemplateCreated($id, $template->code, $template->name, new DateTimeImmutable()));

        return $template;
    }

    public static function reconstitute(
        ShiftTemplateId $id,
        string $code,
        string $name,
        ShiftWindow $shiftWindow,
        int $breakMinutes,
        int $lateToleranceMinutes,
        OvertimeRules $overtimeRules,
        FlexibilityRules $flexibilityRules,
        ?string $payrollAttributionRule,
        bool $active,
    ): self {
        return new self($id, $code, $name, $shiftWindow, $breakMinutes, $lateToleranceMinutes, $overtimeRules, $flexibilityRules, $payrollAttributionRule, $active);
    }

    public function updateDetails(
        string $name,
        ShiftWindow $shiftWindow,
        int $breakMinutes,
        int $lateToleranceMinutes,
        OvertimeRules $overtimeRules,
        FlexibilityRules $flexibilityRules,
        ?string $payrollAttributionRule,
    ): void {
        self::validate($shiftWindow, $breakMinutes, $payrollAttributionRule);

        $this->name = trim($name);
        $this->shiftWindow = $shiftWindow;
        $this->breakMinutes = $breakMinutes;
        $this->lateToleranceMinutes = $lateToleranceMinutes;
        $this->overtimeRules = $overtimeRules;
        $this->flexibilityRules = $flexibilityRules;
        $this->payrollAttributionRule = $payrollAttributionRule;
        $this->record(new ShiftTemplateUpdated($this->id, new DateTimeImmutable()));
    }

    public function activate(): void
    {
        if ($this->active) return;
        $this->active = true;
        $this->record(new ShiftTemplateActivated($this->id, new DateTimeImmutable()));
    }

    public function deactivate(): void
    {
        if (! $this->active) return;
        $this->active = false;
        $this->record(new ShiftTemplateDeactivated($this->id, new DateTimeImmutable()));
    }

    public function id(): ShiftTemplateId { return $this->id; }
    public function code(): string { return $this->code; }
    public function name(): string { return $this->name; }
    public function shiftWindow(): ShiftWindow { return $this->shiftWindow; }
    public function breakMinutes(): int { return $this->breakMinutes; }
    public function lateToleranceMinutes(): int { return $this->lateToleranceMinutes; }
    public function overtimeRules(): OvertimeRules { return $this->overtimeRules; }
    public function flexibilityRules(): FlexibilityRules { return $this->flexibilityRules; }
    public function payrollAttributionRule(): ?string { return $this->payrollAttributionRule; }
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

    private static function validate(ShiftWindow $shiftWindow, int $breakMinutes, ?string $payrollAttributionRule): void
    {
        if ($shiftWindow->isOvernight && blank($payrollAttributionRule)) {
            throw new InvalidShiftTemplateException('Overnight shift requires payroll attribution rule.');
        }

        if ($breakMinutes >= $shiftWindow->durationMinutes()) {
            throw new InvalidShiftTemplateException('Break minutes must be less than shift duration.');
        }
    }
}
