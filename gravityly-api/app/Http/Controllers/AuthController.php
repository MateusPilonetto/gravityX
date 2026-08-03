<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
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
            'data'    => [
                'user'  => new UserResource($data['user']),
                'token' => $data['token'],
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Login successful',
            'data'    => [
                'user'  => new UserResource($data['user']),
                'token' => $data['token'],
            ],
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }

    public function search(Request $request) 
    {
        $term = $request->query('q'); 

        $users = User::where('username', 'LIKE', '%' . $term . '%')->get();

        return response()->json($users);
    }

    public function updateProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'bio'      => ['nullable', 'string', 'max:1000'],
            'avatar'   => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'], // Limite de 2MB
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->bio = $validated['bio'] ?? null;

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->profile_photo_url = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'data'    => new \App\Http\Resources\UserResource($user)
        ], 200);
    }
}