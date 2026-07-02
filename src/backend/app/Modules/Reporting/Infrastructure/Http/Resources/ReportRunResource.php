<?php

namespace App\Modules\Reporting\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportRunResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->resource->getId(),
            'report_definition_id' => $this->resource->getReportDefinitionId(),
            'requested_by' => $this->resource->getRequestedBy(),
            'filters' => $this->resource->getFilters(),
            'status' => $this->resource->getStatus()->value,
            'result' => $this->resource->getResult(),
            'error' => $this->resource->getError(),
            'started_at' => $this->resource->getStartedAt(),
            'completed_at' => $this->resource->getCompletedAt(),
        ];
    }
}
