<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
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
        $data = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'User registered successfully',
            'data'    => $data
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Login successful',
            'data'    => $data
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()
        ], 200);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'bio'      => ['nullable', 'string', 'max:1000'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data'    => $user
        ], 200);
    }
}