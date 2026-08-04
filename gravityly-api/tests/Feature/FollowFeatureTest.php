<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('follows and unfollows a user with authoritative relationship counts', function () {
    $viewer = User::factory()->create(['username' => 'viewer']);
    $target = User::factory()->create(['username' => 'target']);

    Sanctum::actingAs($viewer);

    $this->postJson("/api/users/{$target->username}/follow")
        ->assertOk()
        ->assertJsonPath('is_following', true)
        ->assertJsonPath('followers_count', 1)
        ->assertJsonPath('viewer_following_count', 1);

    $this->assertDatabaseHas('follows', [
        'follower_id' => $viewer->id,
        'following_id' => $target->id,
    ]);

    $this->postJson("/api/users/{$target->username}/follow")
        ->assertOk()
        ->assertJsonPath('is_following', true)
        ->assertJsonPath('followers_count', 1);

    $this->assertDatabaseCount('follows', 1);

    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.following_count', 1);

    Sanctum::actingAs($target);

    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.followers_count', 1);

    Sanctum::actingAs($viewer);

    $this->deleteJson("/api/users/{$target->username}/follow")
        ->assertOk()
        ->assertJsonPath('is_following', false)
        ->assertJsonPath('followers_count', 0)
        ->assertJsonPath('viewer_following_count', 0);

    $this->assertDatabaseMissing('follows', [
        'follower_id' => $viewer->id,
        'following_id' => $target->id,
    ]);
});

it('does not allow following yourself', function () {
    $user = User::factory()->create(['username' => 'self']);

    Sanctum::actingAs($user);

    $this->postJson('/api/users/self/follow')
        ->assertUnprocessable()
        ->assertJsonPath('message', 'You cannot follow yourself.');

    $this->assertDatabaseCount('follows', 0);
});
