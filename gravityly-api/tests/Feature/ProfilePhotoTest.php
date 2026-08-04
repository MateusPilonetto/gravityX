<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function profilePhotoContents(mixed $value): string
{
    if (is_resource($value)) {
        rewind($value);

        return stream_get_contents($value) ?: '';
    }

    return is_string($value) ? $value : '';
}

it('stores and returns a profile photo through PostgreSQL-backed media', function () {
    $user = User::factory()->create(['username' => 'photo-user']);
    $avatar = UploadedFile::fake()->image('avatar.jpg');
    $avatarData = $avatar->get();

    Sanctum::actingAs($user);

    $this->put('/api/me', [
        'name' => 'Photo User',
        'username' => 'photo-user',
        'bio' => 'Profile photo test',
        'avatar' => $avatar,
    ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.username', 'photo-user');

    $user->refresh();

    expect($user->profile_photo_url)->toBe('/media/avatars/'.$user->id);
    expect($user->profile_photo_mime_type)->toBe('image/jpeg');
    expect(profilePhotoContents($user->profile_photo_data))->toBe($avatarData);

    $this->get($user->profile_photo_url)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertContent($avatarData);
});

it('accepts the multipart method override used by the profile editor', function () {
    $user = User::factory()->create(['username' => 'method-override-user']);

    Sanctum::actingAs($user);

    $this->post('/api/me', [
        '_method' => 'PUT',
        'name' => 'Method Override User',
        'username' => 'method-override-user',
        'bio' => 'Sent as multipart form data',
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath(
            'data.profile_photo_url',
            fn (string $url): bool => str_starts_with($url, '/media/avatars/')
        );
});

it('replaces the database record for an existing profile photo', function () {
    $user = User::factory()->create([
        'username' => 'replacement-user',
        'profile_photo_url' => '/media/avatars/1',
        'profile_photo_data' => 'previous avatar',
        'profile_photo_mime_type' => 'image/jpeg',
    ]);

    Sanctum::actingAs($user);

    $this->put('/api/me', [
        'name' => $user->name,
        'username' => $user->username,
        'bio' => $user->bio,
        'avatar' => UploadedFile::fake()->image('replacement-avatar.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertOk();

    $user->refresh();

    expect($user->profile_photo_url)->toBe('/media/avatars/'.$user->id);
    expect($user->profile_photo_data)->not->toBe('previous avatar');
    expect($user->profile_photo_mime_type)->toBe('image/jpeg');
});

it('accepts profile photos up to 5 MB', function () {
    $user = User::factory()->create(['username' => 'larger-photo-user']);

    Sanctum::actingAs($user);

    $this->put('/api/me', [
        'name' => $user->name,
        'username' => $user->username,
        'bio' => $user->bio,
        'avatar' => UploadedFile::fake()->image('larger-avatar.jpg')->size(4096),
    ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.profile_photo_url', '/media/avatars/'.$user->id);
});
