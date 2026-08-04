<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('requires authentication for every post interaction endpoint', function () {
    $author = User::factory()->create();
    $post = $author->posts()->create([
        'body' => 'A protected post.',
    ]);

    $this->getJson('/api/posts')->assertUnauthorized();
    $this->postJson('/api/posts', ['body' => 'A new post.'])->assertUnauthorized();
    $this->getJson("/api/users/{$author->username}/posts")->assertUnauthorized();
    $this->getJson("/api/posts/{$post->id}")->assertUnauthorized();
    $this->deleteJson("/api/posts/{$post->id}")->assertUnauthorized();
    $this->postJson("/api/posts/{$post->id}/likes")->assertUnauthorized();
    $this->deleteJson("/api/posts/{$post->id}/likes")->assertUnauthorized();
    $this->getJson("/api/posts/{$post->id}/comments")->assertUnauthorized();
    $this->postJson("/api/posts/{$post->id}/comments", ['body' => 'A comment.'])
        ->assertUnauthorized();
});

it('creates a post for the authenticated user and ignores a supplied author', function () {
    $author = User::factory()->create([
        'name' => 'Post Author',
        'username' => 'post-author',
    ]);
    $otherUser = User::factory()->create();

    Sanctum::actingAs($author);

    $this->postJson('/api/posts', [
        'caption' => '  A caption  ',
        'body' => '  A complete post body.  ',
        'user_id' => $otherUser->id,
    ])
        ->assertCreated()
        ->assertJsonPath('post.caption', 'A caption')
        ->assertJsonPath('post.body', 'A complete post body.')
        ->assertJsonPath('post.image_url', null)
        ->assertJsonPath('post.user.id', $author->id)
        ->assertJsonPath('post.user.username', 'post-author')
        ->assertJsonPath('post.likes_count', 0)
        ->assertJsonPath('post.comments_count', 0)
        ->assertJsonPath('post.is_liked', false)
        ->assertJsonPath('post.can_delete', true)
        ->assertJsonPath('post.comments', []);

    $this->assertDatabaseHas('posts', [
        'user_id' => $author->id,
        'caption' => 'A caption',
        'body' => 'A complete post body.',
    ]);
    $this->assertDatabaseMissing('posts', [
        'user_id' => $otherUser->id,
        'body' => 'A complete post body.',
    ]);
});

it('creates an image-only post and returns its public image URL', function () {
    Storage::fake('public');
    $author = User::factory()->create();

    Sanctum::actingAs($author);

    $response = $this->post('/api/posts', [
        'image' => UploadedFile::fake()->image('post-image.png', 1200, 800),
    ], ['Accept' => 'application/json']);

    $response
        ->assertCreated()
        ->assertJsonPath('post.caption', null)
        ->assertJsonPath('post.body', null)
        ->assertJsonPath('post.user.id', $author->id);

    $post = Post::query()->sole();

    expect($post->image_path)->toStartWith('posts/');
    $response->assertJsonPath('post.image_url', Storage::disk('public')->url($post->image_path));
    Storage::disk('public')->assertExists($post->image_path);
});

it('only accepts JPG, JPEG, PNG, and WebP post images up to 5 MB', function () {
    $author = User::factory()->create();

    Sanctum::actingAs($author);

    $this->post('/api/posts', [
        'image' => UploadedFile::fake()->image('post-image.gif'),
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);

    $this->post('/api/posts', [
        'image' => UploadedFile::fake()->image('post-image.png')->size(5121),
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('normalizes content and rejects empty posts and comments', function () {
    $author = User::factory()->create();
    $post = $author->posts()->create([
        'body' => 'A post that can receive comments.',
    ]);

    Sanctum::actingAs($author);

    $this->postJson('/api/posts', [
        'caption' => "  \n\t  ",
        'body' => '   ',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['caption', 'body']);

    $this->postJson("/api/posts/{$post->id}/comments", ['body' => '   '])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);
});

it('lists and shows posts with author, interaction state, and comments', function () {
    $author = User::factory()->create([
        'name' => 'Author',
        'username' => 'author',
    ]);
    $viewer = User::factory()->create();
    $commenter = User::factory()->create([
        'name' => 'Comment Author',
        'username' => 'comment-author',
    ]);
    $post = $author->posts()->create([
        'caption' => 'Post title',
        'body' => 'Post body',
    ]);

    $viewer->likes()->create(['post_id' => $post->id]);
    $commenter->comments()->create([
        'post_id' => $post->id,
        'body' => 'First comment',
    ]);

    Sanctum::actingAs($viewer);

    $this->getJson('/api/posts')
        ->assertOk()
        ->assertJsonPath('posts.0.id', $post->id)
        ->assertJsonFragment([
            'id' => $post->id,
            'caption' => 'Post title',
            'body' => 'Post body',
            'likes_count' => 1,
            'comments_count' => 1,
            'is_liked' => true,
        ]);

    $this->getJson("/api/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('post.id', $post->id)
        ->assertJsonPath('post.user.id', $author->id)
        ->assertJsonPath('post.user.name', 'Author')
        ->assertJsonPath('post.user.username', 'author')
        ->assertJsonPath('post.user.posts_count', 1)
        ->assertJsonPath('post.likes_count', 1)
        ->assertJsonPath('post.comments_count', 1)
        ->assertJsonPath('post.is_liked', true)
        ->assertJsonPath('post.can_delete', false)
        ->assertJsonPath('post.comments.0.body', 'First comment')
        ->assertJsonPath('post.comments.0.user.id', $commenter->id)
        ->assertJsonPath('post.comments.0.user.username', 'comment-author');
});

it('lists only the requested profile posts with the viewer interaction state', function () {
    $profileUser = User::factory()->create([
        'name' => 'Profile Author',
        'username' => 'profile-author',
    ]);
    $viewer = User::factory()->create();
    $otherUser = User::factory()->create();
    $olderPost = $profileUser->posts()->create([
        'body' => 'An earlier profile post.',
    ]);
    $olderPost->forceFill(['created_at' => now()->subMinute()])->save();
    $newerPost = $profileUser->posts()->create([
        'body' => 'The latest profile post.',
    ]);
    $otherPost = $otherUser->posts()->create([
        'body' => 'A post that belongs to somebody else.',
    ]);

    $viewer->likes()->create(['post_id' => $olderPost->id]);

    Sanctum::actingAs($viewer);

    $this->getJson('/api/users/profile-author/posts')
        ->assertOk()
        ->assertJsonCount(2, 'posts')
        ->assertJsonPath('posts.0.id', $newerPost->id)
        ->assertJsonPath('posts.0.user.id', $profileUser->id)
        ->assertJsonPath('posts.0.is_liked', false)
        ->assertJsonPath('posts.1.id', $olderPost->id)
        ->assertJsonPath('posts.1.user.id', $profileUser->id)
        ->assertJsonPath('posts.1.is_liked', true)
        ->assertJsonMissing(['id' => $otherPost->id]);
});

it('returns 404 when requesting posts for a profile that does not exist', function () {
    $viewer = User::factory()->create();

    Sanctum::actingAs($viewer);

    $this->getJson('/api/users/unknown-user/posts')
        ->assertNotFound()
        ->assertJsonPath('message', 'Resource not found.');
});

it('returns a JSON 404 response for a post that does not exist', function () {
    $viewer = User::factory()->create();

    Sanctum::actingAs($viewer);

    $this->getJson('/api/posts/999999')
        ->assertNotFound()
        ->assertJsonPath('message', 'Resource not found.');
});

it('likes and unlikes a post using the authenticated user', function () {
    $author = User::factory()->create();
    $viewer = User::factory()->create();
    $otherViewer = User::factory()->create();
    $post = $author->posts()->create([
        'body' => 'A post to like.',
    ]);

    Sanctum::actingAs($viewer);

    $this->postJson("/api/posts/{$post->id}/likes", ['user_id' => $otherViewer->id])
        ->assertOk()
        ->assertJsonPath('post.is_liked', true)
        ->assertJsonPath('post.likes_count', 1);

    $this->assertDatabaseHas('likes', [
        'user_id' => $viewer->id,
        'post_id' => $post->id,
    ]);
    $this->assertDatabaseMissing('likes', [
        'user_id' => $otherViewer->id,
        'post_id' => $post->id,
    ]);

    $this->postJson("/api/posts/{$post->id}/likes")
        ->assertOk()
        ->assertJsonPath('post.likes_count', 1);

    $this->assertDatabaseCount('likes', 1);

    Sanctum::actingAs($otherViewer);

    $this->getJson("/api/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('post.likes_count', 1)
        ->assertJsonPath('post.is_liked', false);

    Sanctum::actingAs($viewer);

    $this->deleteJson("/api/posts/{$post->id}/likes")
        ->assertOk()
        ->assertJsonPath('post.is_liked', false)
        ->assertJsonPath('post.likes_count', 0);

    $this->assertDatabaseMissing('likes', [
        'user_id' => $viewer->id,
        'post_id' => $post->id,
    ]);
});

it('adds and lists comments for the authenticated user', function () {
    $author = User::factory()->create();
    $commenter = User::factory()->create([
        'name' => 'Authenticated Commenter',
        'username' => 'authenticated-commenter',
    ]);
    $otherUser = User::factory()->create();
    $post = $author->posts()->create([
        'body' => 'A post that can receive comments.',
    ]);

    Sanctum::actingAs($commenter);

    $this->postJson("/api/posts/{$post->id}/comments", [
        'body' => 'A new comment.',
        'user_id' => $otherUser->id,
    ])
        ->assertCreated()
        ->assertJsonPath('comment.post_id', $post->id)
        ->assertJsonPath('comment.body', 'A new comment.')
        ->assertJsonPath('comment.user.id', $commenter->id)
        ->assertJsonPath('comment.user.username', 'authenticated-commenter')
        ->assertJsonPath('comments_count', 1);

    $this->assertDatabaseHas('comments', [
        'user_id' => $commenter->id,
        'post_id' => $post->id,
        'body' => 'A new comment.',
    ]);
    $this->assertDatabaseMissing('comments', [
        'user_id' => $otherUser->id,
        'post_id' => $post->id,
        'body' => 'A new comment.',
    ]);

    $this->getJson("/api/posts/{$post->id}/comments")
        ->assertOk()
        ->assertJsonPath('comments.0.body', 'A new comment.')
        ->assertJsonPath('comments.0.user.id', $commenter->id);
});

it('only allows the post author to delete a post', function () {
    $author = User::factory()->create();
    $viewer = User::factory()->create();
    $commenter = User::factory()->create();
    $post = $author->posts()->create([
        'body' => 'A post with interactions.',
    ]);

    $viewer->likes()->create(['post_id' => $post->id]);
    $commenter->comments()->create([
        'post_id' => $post->id,
        'body' => 'A comment that should be deleted with the post.',
    ]);

    Sanctum::actingAs($viewer);

    $this->deleteJson("/api/posts/{$post->id}")
        ->assertForbidden()
        ->assertJsonPath('message', 'You are not allowed to delete this post.');

    $this->assertDatabaseHas('posts', ['id' => $post->id]);

    Sanctum::actingAs($author);

    $this->deleteJson("/api/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Post deleted successfully.');

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    $this->assertDatabaseMissing('likes', ['post_id' => $post->id]);
    $this->assertDatabaseMissing('comments', ['post_id' => $post->id]);
});

it('removes the public image when the post author deletes a post', function () {
    Storage::fake('public');
    $author = User::factory()->create();
    $imagePath = 'posts/post-image.png';
    $post = $author->posts()->create([
        'body' => 'A post with an image.',
        'image_path' => $imagePath,
    ]);
    Storage::disk('public')->put($imagePath, 'post image');

    Sanctum::actingAs($author);

    $this->deleteJson("/api/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Post deleted successfully.');

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    Storage::disk('public')->assertMissing($imagePath);
});
