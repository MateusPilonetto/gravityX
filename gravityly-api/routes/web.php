<?php

use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/media/avatars/{user}', [MediaController::class, 'avatar'])
    ->whereNumber('user')
    ->name('media.avatar');

Route::get('/media/posts/{post}', [MediaController::class, 'post'])
    ->whereNumber('post')
    ->name('media.post');

Route::get('/storage/avatars/{filename}', function ($filename) {
    $avatarPath = storage_path('app/public/avatars/'.$filename);

    if (! file_exists($avatarPath)) {
        abort(404);
    }

    return response()->file($avatarPath);
});
