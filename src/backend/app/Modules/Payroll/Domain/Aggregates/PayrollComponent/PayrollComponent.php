<?php

namespace App\Modules\Payroll\Domain\Aggregates\PayrollComponent;

use App\Modules\Payroll\Domain\ValueObjects\ComponentCategory;
use App\Modules\Payroll\Domain\ValueObjects\CalculationType;
use App\Modules\Payroll\Domain\ValueObjects\Money;

class PayrollComponent
{
    private function __construct(
        private PayrollComponentId $id,
        private string $code,
        private string $name,
        private ComponentCategory $category,
        private CalculationType $calculationType,
        private ?string $percentBaseComponentId,
        private ?Money $defaultAmount,
        private ?float $defaultPercent,
        private bool $taxable,
        private bool $active,
    ) {}

    public static function create(
        PayrollComponentId $id,
        string $code,
        string $name,
        ComponentCategory $category,
        CalculationType $calculationType,
        ?string $percentBaseComponentId = null,
        ?Money $defaultAmount = null,
        ?float $defaultPercent = null,
        bool $taxable = true,
    ): self {
        if ($calculationType === CalculationType::PercentOfComponent && $percentBaseComponentId === null) {
            throw new \InvalidArgumentException('percent_of_component requires percent_base_component_id.');
        }
        if ($calculationType === CalculationType::FixedAmount && $defaultAmount === null) {
            throw new \InvalidArgumentException('fixed_amount requires default_amount.');
        }
        return new self($id, $code, $name, $category, $calculationType, $percentBaseComponentId, $defaultAmount, $defaultPercent, $taxable, true);
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function updateConfig(
        ?Money $defaultAmount = null,
        ?float $defaultPercent = null,
        ?bool $taxable = null,
    ): void {
        if ($defaultAmount !== null) $this->defaultAmount = $defaultAmount;
        if ($defaultPercent !== null) $this->defaultPercent = $defaultPercent;
        if ($taxable !== null) $this->taxable = $taxable;
    }

    public function getId(): PayrollComponentId { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getCategory(): ComponentCategory { return $this->category; }
    public function getCalculationType(): CalculationType { return $this->calculationType; }
    public function getPercentBaseComponentId(): ?string { return $this->percentBaseComponentId; }
    public function getDefaultAmount(): ?Money { return $this->defaultAmount; }
    public function getDefaultPercent(): ?float { return $this->defaultPercent; }
    public function isTaxable(): bool { return $this->taxable; }
    public function isActive(): bool { return $this->active; }
}
