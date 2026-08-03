<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [ProfileController::class, 'show']);
    Route::put('/me', [ProfileController::class, 'update']);
    Route::get('/search', [\App\Http\Controllers\ProfileController::class, 'search']);
    Route::get('/users/{username}', [\App\Http\Controllers\ProfileController::class, 'show'])->where('username', '.*');    
    Route::post('/users/{username}/follow', [ProfileController::class, 'follow']);
    Route::delete('/users/{username}/follow', [ProfileController::class, 'unfollow']);
});
