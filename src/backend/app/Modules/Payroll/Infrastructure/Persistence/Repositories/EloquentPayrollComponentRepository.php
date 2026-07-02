<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Repositories;

use App\Modules\Payroll\Domain\Aggregates\PayrollComponent\{PayrollComponent, PayrollComponentId};
use App\Modules\Payroll\Domain\Repositories\PayrollComponentRepositoryInterface;
use App\Modules\Payroll\Domain\ValueObjects\{CalculationType, ComponentCategory, Money};
use App\Modules\Payroll\Infrastructure\Persistence\Eloquent\PayrollComponentModel;
use ReflectionClass;

class EloquentPayrollComponentRepository implements PayrollComponentRepositoryInterface
{
    public function save(PayrollComponent $c): void
    {
        PayrollComponentModel::updateOrCreate(
            ['id' => $c->getId()->value],
            [
                'code' => $c->getCode(),
                'name' => $c->getName(),
                'category' => $c->getCategory()->value,
                'calculation_type' => $c->getCalculationType()->value,
                'percent_base_component_id' => $c->getPercentBaseComponentId(),
                'default_amount' => $c->getDefaultAmount()?->toDecimal(),
                'default_percent' => $c->getDefaultPercent(),
                'taxable' => $c->isTaxable(),
                'active' => $c->isActive(),
            ]
        );
    }

    public function findById(PayrollComponentId $id): ?PayrollComponent
    {
        $m = PayrollComponentModel::find($id->value);
        return $m ? $this->toAggregate($m) : null;
    }

    public function findByCode(string $code): ?PayrollComponent
    {
        $m = PayrollComponentModel::where('code', $code)->first();
        return $m ? $this->toAggregate($m) : null;
    }

    public function findActive(): array
    {
        return PayrollComponentModel::where('active', true)->get()
            ->map(fn($m) => $this->toAggregate($m))->all();
    }

    private function toAggregate(PayrollComponentModel $m): PayrollComponent
    {
        $ref = new ReflectionClass(PayrollComponent::class);
        $c = $ref->newInstanceWithoutConstructor();
        $props = [
            'id' => PayrollComponentId::fromString($m->id),
            'code' => $m->code,
            'name' => $m->name,
            'category' => ComponentCategory::from($m->category),
            'calculationType' => CalculationType::from($m->calculation_type),
            'percentBaseComponentId' => $m->percent_base_component_id,
            'defaultAmount' => $m->default_amount !== null ? Money::fromDecimal((float)$m->default_amount) : null,
            'defaultPercent' => $m->default_percent !== null ? (float)$m->default_percent : null,
            'taxable' => (bool)$m->taxable,
            'active' => (bool)$m->active,
        ];
        foreach ($props as $name => $val) {
            $ref->getProperty($name)->setValue($c, $val);
        }
        return $c;
    }
}
