<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

$usernamePattern = '[^/]+';

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () use ($usernamePattern) {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [ProfileController::class, 'show']);
    Route::put('/me', [ProfileController::class, 'update']);
    Route::get('/search', [ProfileController::class, 'search']);
    Route::get('/users/{username}', [ProfileController::class, 'show'])
        ->where('username', $usernamePattern);
    Route::post('/users/{username}/follow', [ProfileController::class, 'follow'])
        ->where('username', $usernamePattern);
    Route::delete('/users/{username}/follow', [ProfileController::class, 'unfollow'])
        ->where('username', $usernamePattern);
});
