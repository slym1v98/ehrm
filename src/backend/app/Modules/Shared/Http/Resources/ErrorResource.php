<?php

namespace App\Modules\Shared\Http\Resources;

use App\Modules\Shared\Exceptions\AppException;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @property-read AppException $resource
 */
class ErrorResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var AppException $exception */
        $exception = $this->resource;

        return [
            'error' => [
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                'details' => $exception->details,
                'trace_id' => (string) Str::uuid(),
            ],
        ];
    }
}
