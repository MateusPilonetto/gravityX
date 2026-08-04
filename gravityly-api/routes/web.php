<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/storage/avatars/{filename}', function ($filename) {
    $avatarPath = storage_path('app/public/avatars/'.$filename);

    if (! file_exists($avatarPath)) {
        abort(404);
    }

    return response()->file($avatarPath);
});
