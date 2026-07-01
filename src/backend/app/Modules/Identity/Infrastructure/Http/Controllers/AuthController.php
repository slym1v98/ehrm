<?php

namespace App\Modules\Identity\Infrastructure\Http\Controllers;

use App\Modules\Identity\Application\Services\AuthenticationService;
use App\Modules\Identity\Infrastructure\Http\Requests\ChangePasswordRequest;
use App\Modules\Identity\Infrastructure\Http\Requests\LoginRequest;
use App\Modules\Identity\Infrastructure\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController
{
    public function __construct(private AuthenticationService $auth) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request->email, $request->password);
        return response()->json([
            'data' => [
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
                'user' => new UserResource($result['user']->load(['userRoles.role', 'dataScopeAssignments'])),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());
        return response()->json(['data' => null]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->load(['userRoles.role', 'dataScopeAssignments'])),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->auth->changePassword($request->user(), $request->current_password, $request->new_password);
        return response()->json(['data' => null]);
    }
}
