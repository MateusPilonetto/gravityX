<?php

use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication to load profile suggestions', function () {
    $this->getJson('/api/suggestions')->assertUnauthorized();
});

it('prioritizes mutual connections and excludes profiles the viewer already follows', function () {
    $viewer = User::factory()->create(['username' => 'viewer']);
    $followedUser = User::factory()->create(['username' => 'already-followed']);
    $mutualSuggestedUser = User::factory()->create(['username' => 'mutual-suggested']);
    $mostFollowedUser = User::factory()->create(['username' => 'most-followed']);
    $followerOne = User::factory()->create();
    $followerTwo = User::factory()->create();

    Follow::query()->create([
        'follower_id' => $viewer->id,
        'following_id' => $followedUser->id,
    ]);
    Follow::query()->create([
        'follower_id' => $followedUser->id,
        'following_id' => $mutualSuggestedUser->id,
    ]);
    Follow::query()->create([
        'follower_id' => $followerOne->id,
        'following_id' => $mostFollowedUser->id,
    ]);
    Follow::query()->create([
        'follower_id' => $followerTwo->id,
        'following_id' => $mostFollowedUser->id,
    ]);

    Sanctum::actingAs($viewer);

    $response = $this->getJson('/api/suggestions')
        ->assertOk()
        ->assertJsonPath('data.0.id', $mutualSuggestedUser->id)
        ->assertJsonPath('data.0.mutual_connections_count', 1)
        ->assertJsonPath('data.1.id', $mostFollowedUser->id)
        ->assertJsonPath('data.1.followers_count', 2)
        ->assertJsonPath('data.1.mutual_connections_count', 0)
        ->assertJsonMissingPath('data.0.email');

    $suggestedIds = collect($response->json('data'))->pluck('id');

    expect($suggestedIds)
        ->not->toContain($viewer->id)
        ->not->toContain($followedUser->id);
});
