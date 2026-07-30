<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create($data);
        $token = $this->issueToken($user);

        return [
            'user'  => $user,
            'token' => $token->plainTextToken,
        ];
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        // one single message for "no such user" and "wrong password" on purpose
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $token = $this->issueToken($user);

        return [
            'user'  => $user,
            'token' => $token->plainTextToken,
        ];
    }

    private function issueToken(User $user): NewAccessToken
    {
        return $user->createToken('api-token');
    }
}