<?php

use App\Http\Controllers\MediaController;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::get('/media/avatars/{user}', [MediaController::class, 'avatar'])
    ->whereNumber('user')
    ->middleware(SubstituteBindings::class)
    ->name('media.avatar');

Route::get('/media/posts/{post}', [MediaController::class, 'post'])
    ->whereNumber('post')
    ->middleware(SubstituteBindings::class)
    ->name('media.post');

Route::get('/media/stories/{story}', [MediaController::class, 'story'])
    ->whereNumber('story')
    ->middleware('signed:relative')
    ->name('media.story');
