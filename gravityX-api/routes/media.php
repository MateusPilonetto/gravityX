<?php

use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

Route::get('/media/avatars/{user}', [MediaController::class, 'avatar'])
    ->whereNumber('user')
    ->name('media.avatar');

Route::get('/media/posts/{post}', [MediaController::class, 'post'])
    ->whereNumber('post')
    ->name('media.post');
