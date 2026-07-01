<?php

namespace App\Modules\Identity\Infrastructure\Http\Middleware;

use App\Modules\Identity\Application\Services\AuthorizationService;
use App\Modules\Shared\Exceptions\AppException;
use App\Modules\Shared\Http\Resources\ErrorResource;
use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function __construct(private AuthorizationService $authz) {}

    public function handle(Request $request, Closure $next, string $permissionCode)
    {
        $user = $request->user();

        if (! $user || ! $this->authz->userHasPermission((string) $user->id, $permissionCode)) {
            $exception = new class('PERMISSION_DENIED', "Missing permission: {$permissionCode}") extends AppException {
                public function getHttpStatus(): int { return 403; }
            };
            return response()->json(
                (new ErrorResource($exception))->toArray($request),
                403,
            );
        }

        return $next($request);
    }
}
