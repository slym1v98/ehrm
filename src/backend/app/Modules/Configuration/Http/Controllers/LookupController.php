<?php

namespace App\Modules\Configuration\Http\Controllers;

use App\Modules\Configuration\Domain\Repositories\LookupRepositoryInterface;
use App\Modules\Configuration\Http\Requests\StoreLookupGroupRequest;
use App\Modules\Configuration\Http\Requests\StoreLookupValueRequest;
use App\Modules\Configuration\Http\Resources\LookupGroupResource;
use App\Modules\Configuration\Http\Resources\LookupValueResource;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LookupController
{
    public function index(Request $request, LookupRepositoryInterface $lookups): PaginatedCollection
    {
        return new PaginatedCollection($lookups->listGroups((int) $request->integer('per_page', 20)), LookupGroupResource::class);
    }

    public function store(StoreLookupGroupRequest $request, LookupRepositoryInterface $lookups): LookupGroupResource
    {
        return new LookupGroupResource($lookups->saveGroup($request->validated())->load('values'));
    }

    public function show(string $id, LookupRepositoryInterface $lookups): LookupGroupResource
    {
        return new LookupGroupResource($lookups->findGroup($id) ?? throw new NotFoundHttpException('Lookup group not found.'));
    }

    public function storeValue(string $id, StoreLookupValueRequest $request, LookupRepositoryInterface $lookups): LookupValueResource
    {
        $group = $lookups->findGroup($id) ?? throw new NotFoundHttpException('Lookup group not found.');

        return new LookupValueResource($lookups->saveValue($group, $request->validated()));
    }
}
