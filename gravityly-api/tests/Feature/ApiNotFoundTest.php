<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns a JSON error for an unknown API route', function () {
    $this->getJson('/api/unknown-resource')
        ->assertNotFound()
        ->assertJsonPath('message', 'Resource not found.');
});

it('returns the same JSON error when a requested profile does not exist', function () {
    $viewer = User::factory()->create();

    Sanctum::actingAs($viewer);

    $this->getJson('/api/users/user-that-does-not-exist')
        ->assertNotFound()
        ->assertJsonPath('message', 'Resource not found.');
});
