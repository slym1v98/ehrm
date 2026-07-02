<?php

namespace App\Modules\Reporting\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportDefinitionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->resource->getId(),
            'code' => $this->resource->getCode(),
            'name' => $this->resource->getName(),
            'description' => $this->resource->getDescription(),
            'filters_schema' => $this->resource->getFiltersSchema(),
            'columns_schema' => $this->resource->getColumnsSchema(),
            'is_active' => $this->resource->isActive(),
        ];
    }
}
