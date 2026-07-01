<?php

use App\Modules\Shared\Exceptions\AppException;
use App\Modules\Shared\Exceptions\ValidationException as SharedValidationException;
use App\Modules\Shared\Http\Middleware\ForceJsonMiddleware;
use App\Modules\Shared\Http\Resources\ErrorResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AppException $exception, $request) {
            return response()->json(
                (new ErrorResource($exception))->toArray($request),
                $exception->getHttpStatus(),
            );
        });

        $exceptions->render(function (ValidationException $exception, $request) {
            $appException = new SharedValidationException(
                details: collect($exception->errors())->map(fn ($messages, $field) => [
                    'field' => $field,
                    'message' => implode('; ', $messages),
                ])->values()->toArray(),
            );

            return response()->json(
                (new ErrorResource($appException))->toArray($request),
                422,
            );
        });

        $exceptions->render(function (AuthenticationException $exception, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $appException = new class('UNAUTHENTICATED', 'Unauthenticated.') extends AppException {
                    public function getHttpStatus(): int { return 401; }
                };

                return response()->json(
                    (new ErrorResource($appException))->toArray($request),
                    401,
                );
            }
        });
    })->create();
