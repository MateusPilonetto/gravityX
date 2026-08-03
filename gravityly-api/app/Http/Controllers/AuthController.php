<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $registration = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'User registered successfully',
            'data' => [
                'user' => new UserResource($registration['user']),
                'token' => $registration['token'],
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $authentication = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($authentication['user']),
                'token' => $authentication['token'],
            ],
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Successfully logged out',
        ], 200);
    }
}
