<?php

namespace App\Modules\Configuration\Http\Controllers;

use App\Modules\Configuration\Domain\Repositories\NotificationThresholdRepositoryInterface;
use App\Modules\Configuration\Http\Requests\ConfigurationRequest;
use App\Modules\Configuration\Http\Resources\NotificationThresholdResource;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\Request;

class NotificationThresholdController
{
    public function index(Request $request, NotificationThresholdRepositoryInterface $thresholds): PaginatedCollection { return new PaginatedCollection($thresholds->list((int) $request->integer('per_page', 20)), NotificationThresholdResource::class); }
    public function store(ConfigurationRequest $request, NotificationThresholdRepositoryInterface $thresholds): NotificationThresholdResource { return new NotificationThresholdResource($thresholds->save($request->validated())); }
}
