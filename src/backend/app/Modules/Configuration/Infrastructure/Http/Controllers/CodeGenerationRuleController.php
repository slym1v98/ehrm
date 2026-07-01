<?php

namespace App\Modules\Configuration\Infrastructure\Http\Controllers;

use App\Modules\Configuration\Application\Services\CodeGenerator;
use App\Modules\Configuration\Domain\Events\CodeGenerationRuleChanged;
use App\Modules\Configuration\Domain\Repositories\CodeGenerationRuleRepositoryInterface;
use App\Modules\Configuration\Infrastructure\Http\Requests\StoreCodeGenerationRuleRequest;
use App\Modules\Configuration\Infrastructure\Http\Resources\CodeGenerationRuleResource;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class CodeGenerationRuleController
{
    public function index(Request $request, CodeGenerationRuleRepositoryInterface $rules): PaginatedCollection { return new PaginatedCollection($rules->list((int) $request->integer('per_page', 20)), CodeGenerationRuleResource::class); }
    public function store(StoreCodeGenerationRuleRequest $request, CodeGenerationRuleRepositoryInterface $rules): JsonResponse {
        $rule = $rules->save($request->validated());
        Event::dispatch(new CodeGenerationRuleChanged((string) $rule->id, 'upsert', new DateTimeImmutable()));
        return response()->json(['data' => new CodeGenerationRuleResource($rule)], 201);
    }
    public function preview(string $entityType, CodeGenerator $generator): array { return ['data' => ['code' => $generator->preview($entityType)]]; }
    public function next(string $entityType, CodeGenerator $generator): array { return ['data' => ['code' => $generator->next($entityType)]]; }
}
