<?php

namespace App\Modules\Shared\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @property-read LengthAwarePaginator $resource
 */
class PaginatedCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        $pagination = $this->resource;

        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $pagination->currentPage(),
                'per_page' => $pagination->perPage(),
                'total' => $pagination->total(),
                'last_page' => $pagination->lastPage(),
            ],
            'links' => [
                'first' => $pagination->url(1),
                'last' => $pagination->url($pagination->lastPage()),
                'prev' => $pagination->previousPageUrl(),
                'next' => $pagination->nextPageUrl(),
            ],
        ];
    }
}
