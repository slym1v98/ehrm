<?php

namespace App\Modules\Configuration\Infrastructure\Http\Controllers;

use App\Modules\Configuration\Domain\Events\SystemSettingChanged;
use App\Modules\Configuration\Domain\Repositories\SystemSettingRepositoryInterface;
use App\Modules\Configuration\Infrastructure\Http\Requests\StoreSystemSettingRequest;
use App\Modules\Configuration\Infrastructure\Http\Resources\SystemSettingResource;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class SystemSettingController
{
    public function index(Request $request, SystemSettingRepositoryInterface $settings): PaginatedCollection { return new PaginatedCollection($settings->list((int) $request->integer('per_page', 50)), SystemSettingResource::class); }
    public function store(StoreSystemSettingRequest $request, SystemSettingRepositoryInterface $settings): JsonResponse {
        $setting = $settings->save($request->validated());
        Event::dispatch(new SystemSettingChanged((string) $setting->id, (string) $setting->key, 'upsert', new DateTimeImmutable()));
        return response()->json(['data' => new SystemSettingResource($setting)], 201);
    }
}
