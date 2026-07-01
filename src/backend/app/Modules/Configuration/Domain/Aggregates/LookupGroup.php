<?php

namespace App\Modules\Configuration\Domain\Aggregates;

use App\Modules\Configuration\Infrastructure\Persistence\Eloquent\LookupGroupModel;

class LookupGroup
{
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $active,
        public readonly array $values
    ) {}

    public static function fromModel(LookupGroupModel $model): self
    {
        return new self(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            description: $model->description,
            active: $model->active,
            values: $model->values->map(fn ($value) => [
                'id' => $value->id,
                'code' => $value->code,
                'name' => $value->name,
                'description' => $value->description,
                'sort_order' => $value->sort_order,
                'active' => $value->active,
                'metadata' => $value->metadata,
            ])->toArray()
        );
    }
}
