<?php

namespace App\Modules\Configuration\Http\Controllers;

use App\Modules\Configuration\Domain\Repositories\SystemSettingRepositoryInterface;
use App\Modules\Configuration\Http\Requests\ConfigurationRequest;
use App\Modules\Configuration\Http\Resources\SystemSettingResource;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\Request;

class SystemSettingController
{
    public function index(Request $request, SystemSettingRepositoryInterface $settings): PaginatedCollection { return new PaginatedCollection($settings->list((int) $request->integer('per_page', 50)), SystemSettingResource::class); }
    public function store(ConfigurationRequest $request, SystemSettingRepositoryInterface $settings): SystemSettingResource { return new SystemSettingResource($settings->save($request->validated())); }
}
