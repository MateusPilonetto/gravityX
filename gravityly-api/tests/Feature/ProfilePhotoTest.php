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

it('replaces an existing profile photo after the new photo is stored', function () {
    Storage::fake('public');
    $previousAvatarPath = 'avatars/previous-avatar.jpg';
    Storage::disk('public')->put($previousAvatarPath, 'previous avatar');

    $user = User::factory()->create([
        'username' => 'replacement-user',
        'profile_photo_url' => '/storage/'.$previousAvatarPath,
    ]);

    Sanctum::actingAs($user);

    $this->put('/api/me', [
        'name' => $user->name,
        'username' => $user->username,
        'bio' => $user->bio,
        'avatar' => UploadedFile::fake()->image('replacement-avatar.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertOk();

    $newAvatarUrl = $user->refresh()->profile_photo_url;
    $newAvatarPath = Str::after(parse_url($newAvatarUrl, PHP_URL_PATH) ?? $newAvatarUrl, '/storage/');

    expect($newAvatarPath)->not->toBe($previousAvatarPath);
    Storage::disk('public')->assertMissing($previousAvatarPath);
    Storage::disk('public')->assertExists($newAvatarPath);
});
