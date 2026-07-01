<?php

namespace App\Modules\Audit\Infrastructure\Http\Controllers;

use App\Modules\Audit\Infrastructure\Http\Resources\AuditLogResource;
use App\Modules\Audit\Infrastructure\Persistence\Eloquent\AuditLogModel;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\Request;

class AuditLogController
{
    public function index(Request $request): PaginatedCollection
    {
        $query = AuditLogModel::query()->orderByDesc('occurred_at');

        foreach (['actor_user_id', 'action', 'module', 'entity_type', 'entity_id', 'result'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->string($field));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('occurred_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('occurred_at', '<=', $request->date('date_to'));
        }

        return new PaginatedCollection($query->paginate((int) $request->integer('per_page', 20)), AuditLogResource::class);
    }
}
