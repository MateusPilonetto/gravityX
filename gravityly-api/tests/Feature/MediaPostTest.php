<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function postImageContents(mixed $value): string
{
    if (is_resource($value)) {
        rewind($value);

        return stream_get_contents($value) ?: '';
    }

    return is_string($value) ? $value : '';
}

it('stores post images in the database and serves them from a stable media URL', function () {
    $author = User::factory()->create();
    $image = UploadedFile::fake()->image('post-image.png', 1200, 800);
    $imageData = $image->get();

    Sanctum::actingAs($author);

    $this->post('/api/posts', [
        'image' => $image,
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('post.image_url', '/media/posts/1')
        ->assertJsonPath('post.user.id', $author->id);

    $post = Post::query()->sole();

    expect($post->image_path)->toBeNull();
    expect($post->image_mime_type)->toBe('image/png');
    expect(postImageContents($post->image_data))->toBe($imageData);

    $this->get('/media/posts/'.$post->id)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertContent($imageData);
});

it('keeps legacy file-backed post URLs relative to the API origin', function () {
    $author = User::factory()->create();
    $post = $author->posts()->create([
        'body' => 'A legacy post image.',
        'image_path' => 'posts/legacy-image.png',
    ]);

    Sanctum::actingAs($author);

    $this->getJson('/api/posts/'.$post->id)
        ->assertOk()
        ->assertJsonPath('post.image_url', '/storage/posts/legacy-image.png');
});
