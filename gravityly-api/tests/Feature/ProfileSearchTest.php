<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('searches for a username of zero instead of treating it as an empty query', function () {
    $viewer = User::factory()->create(['username' => 'viewer']);
    $target = User::factory()->create([
        'username' => '0',
        'name' => 'Zero User',
    ]);

    Sanctum::actingAs($viewer);

    $this->getJson('/api/search?q=0')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $target->id,
            'username' => '0',
            'name' => 'Zero User',
        ]);
});
