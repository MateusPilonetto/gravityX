<?php

use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication to publish a story', function () {
    $this->post('/api/stories', [
        'media' => UploadedFile::fake()->image('story.png'),
    ], ['Accept' => 'application/json'])
        ->assertUnauthorized();
});

it('uploads an image story for the authenticated user', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->post('/api/stories', [
        'media' => UploadedFile::fake()->image('story.png', 1200, 800),
    ], ['Accept' => 'application/json']);

    $story = Story::query()->sole();

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Story created successfully.')
        ->assertJsonPath('story.id', $story->id)
        ->assertJsonPath('story.user_id', $user->id)
        ->assertJsonPath('story.media_url', '/storage/'.$story->media_path)
        ->assertJsonPath('story.media_type', 'image')
        ->assertJsonMissingPath('story.media_path');

    expect($story->user_id)->toBe($user->id)
        ->and($story->media_type)->toBe('image')
        ->and($story->expires_at)->not->toBeNull();

    Storage::disk('public')->assertExists($story->media_path);
});

it('uploads an MP4 story for the authenticated user', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->post('/api/stories', [
        'media' => UploadedFile::fake()->create('story.mp4', 512, 'video/mp4'),
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('story.media_type', 'video');

    $story = Story::query()->sole();

    expect($story->media_type)->toBe('video');
    Storage::disk('public')->assertExists($story->media_path);
});

it('rejects files that are not supported story media', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->post('/api/stories', [
        'media' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['media']);
});

it('returns the viewer and followed users with their active stories in the feed', function () {
    $viewer = User::factory()->create([
        'name' => 'Viewer',
        'username' => 'viewer',
    ]);
    $followedUser = User::factory()->create([
        'name' => 'Followed user',
        'username' => 'followed-user',
        'email' => 'followed@example.com',
    ]);
    $unfollowedUser = User::factory()->create();

    $viewer->following()->create(['following_id' => $followedUser->id]);

    $viewerStory = $viewer->stories()->create([
        'media_path' => 'stories/viewer.png',
        'media_type' => 'image',
        'expires_at' => now()->addHour(),
    ]);
    $followedStory = $followedUser->stories()->create([
        'media_path' => 'stories/followed.mp4',
        'media_type' => 'video',
        'expires_at' => now()->addHour(),
    ]);
    $followedUser->stories()->create([
        'media_path' => 'stories/expired.png',
        'media_type' => 'image',
        'expires_at' => now()->subSecond(),
    ]);
    $unfollowedUser->stories()->create([
        'media_path' => 'stories/unfollowed.png',
        'media_type' => 'image',
        'expires_at' => now()->addHour(),
    ]);

    Sanctum::actingAs($viewer);

    $response = $this->getJson('/api/posts')->assertOk();
    $storyGroups = collect($response->json('stories'));
    $viewerGroup = $storyGroups->firstWhere('user.id', $viewer->id);
    $followedGroup = $storyGroups->firstWhere('user.id', $followedUser->id);

    expect($storyGroups->pluck('user.id')->all())
        ->toContain($viewer->id, $followedUser->id)
        ->not->toContain($unfollowedUser->id);

    expect($viewerGroup['stories'][0])
        ->toMatchArray([
            'id' => $viewerStory->id,
            'user_id' => $viewer->id,
            'media_url' => '/storage/stories/viewer.png',
            'media_type' => 'image',
        ])
        ->not->toHaveKey('media_path');

    expect($followedGroup['stories'])
        ->toHaveCount(1)
        ->and($followedGroup['stories'][0])
        ->toMatchArray([
            'id' => $followedStory->id,
            'media_url' => '/storage/stories/followed.mp4',
            'media_type' => 'video',
        ]);

    expect($followedGroup['user'])
        ->not->toHaveKey('email');
});

it('prunes expired stories and their stored media', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $expiredPath = 'stories/expired.png';
    $activePath = 'stories/active.png';
    Storage::disk('public')->put($expiredPath, 'expired story');
    Storage::disk('public')->put($activePath, 'active story');

    $expiredStory = $user->stories()->create([
        'media_path' => $expiredPath,
        'media_type' => 'image',
        'expires_at' => now()->subSecond(),
    ]);
    $activeStory = $user->stories()->create([
        'media_path' => $activePath,
        'media_type' => 'image',
        'expires_at' => now()->addHour(),
    ]);

    $this->artisan('model:prune', ['--model' => [Story::class]])
        ->assertSuccessful();

    $this->assertDatabaseMissing('stories', ['id' => $expiredStory->id]);
    $this->assertDatabaseHas('stories', ['id' => $activeStory->id]);
    Storage::disk('public')->assertMissing($expiredPath);
    Storage::disk('public')->assertExists($activePath);
});

it('upgrades a legacy stories table that is missing the media type column', function () {
    Schema::table('stories', function (Blueprint $table) {
        $table->dropColumn('media_type');
    });

    expect(Schema::hasColumn('stories', 'media_type'))->toBeFalse();

    $migration = require database_path('migrations/2026_08_05_000001_add_media_type_to_stories_table.php');
    $migration->up();

    expect(Schema::hasColumn('stories', 'media_type'))->toBeTrue();

    $story = User::factory()->create()->stories()->create([
        'media_path' => 'stories/legacy.png',
        'expires_at' => now()->addHour(),
    ]);

    expect($story->fresh()->media_type)->toBe('image');
});
