<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('stores and returns a profile photo through the public storage path', function () {
    Storage::fake('public');
    $user = User::factory()->create(['username' => 'photo-user']);

    Sanctum::actingAs($user);

    $this->put('/api/me', [
        'name' => 'Photo User',
        'username' => 'photo-user',
        'bio' => 'Profile photo test',
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.username', 'photo-user');

    $photoUrl = $user->refresh()->profile_photo_url;
    $path = Str::after(parse_url($photoUrl, PHP_URL_PATH) ?? $photoUrl, '/storage/');

    expect($photoUrl)->toStartWith('/storage/avatars/');
    Storage::disk('public')->assertExists($path);
});
