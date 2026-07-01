<?php

namespace App\Modules\Configuration\Http\Controllers;

use App\Modules\Configuration\Application\Services\CodeGenerator;
use App\Modules\Configuration\Domain\Repositories\CodeGenerationRuleRepositoryInterface;
use App\Modules\Configuration\Http\Requests\ConfigurationRequest;
use App\Modules\Configuration\Http\Resources\CodeGenerationRuleResource;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\Request;

class CodeGenerationRuleController
{
    public function index(Request $request, CodeGenerationRuleRepositoryInterface $rules): PaginatedCollection { return new PaginatedCollection($rules->list((int) $request->integer('per_page', 20)), CodeGenerationRuleResource::class); }
    public function store(ConfigurationRequest $request, CodeGenerationRuleRepositoryInterface $rules): CodeGenerationRuleResource { return new CodeGenerationRuleResource($rules->save($request->validated())); }
    public function preview(string $entityType, CodeGenerator $generator): array { return ['data' => ['code' => $generator->preview($entityType)]]; }
    public function next(string $entityType, CodeGenerator $generator): array { return ['data' => ['code' => $generator->next($entityType)]]; }
}
