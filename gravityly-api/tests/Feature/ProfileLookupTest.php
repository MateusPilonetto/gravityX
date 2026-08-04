<?php

use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns the requested profile instead of the authenticated user', function () {
    $viewer = User::factory()->create([
        'username' => 'viewer',
        'name' => 'Viewer User',
    ]);
    $target = User::factory()->create([
        // "0" is valid and must not be treated as an absent route parameter.
        'username' => '0',
        'name' => 'Target User',
        'bio' => 'Target profile bio',
    ]);

    Follow::create([
        'follower_id' => $viewer->id,
        'following_id' => $target->id,
    ]);

    Sanctum::actingAs($viewer);

    $this->getJson('/api/users/0')
        ->assertOk()
        ->assertJsonPath('user.id', $target->id)
        ->assertJsonPath('user.username', '0')
        ->assertJsonPath('user.name', 'Target User')
        ->assertJsonPath('user.bio', 'Target profile bio')
        ->assertJsonPath('is_following', true)
        ->assertJsonMissingPath('user.email');
});
