<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PostService
{
    /**
     * @return Collection<int, Post>
     */
    public function listFor(User $viewer): Collection
    {
        return $this->postQuery($viewer)
            ->latest()
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    public function listForUser(User $viewer, User $profileUser): Collection
    {
        return $this->postQuery($viewer)
            ->where('user_id', $profileUser->id)
            ->latest()
            ->get();
    }

    public function findFor(User $viewer, Post $post): Post
    {
        return $this->postQuery($viewer)
            ->with([
                'comments' => fn (HasMany $comments) => $comments
                    ->with('user')
                    ->oldest(),
            ])
            ->findOrFail($post->id);
    }

    /**
     * @param  array{caption?: string|null, body?: string|null}  $attributes
     */
    public function create(User $user, array $attributes, ?UploadedFile $image = null): Post
    {
        $imagePath = null;

        if ($image !== null) {
            $imagePath = $this->storeImage($image);
            $attributes['image_path'] = $imagePath;
        }

        try {
            $post = $user->posts()->create($attributes);
        } catch (Throwable $exception) {
            $this->deleteImage($imagePath);

            throw $exception;
        }

        return $this->findFor($user, $post);
    }

    public function like(User $user, Post $post): Post
    {
        $user->likes()->firstOrCreate(['post_id' => $post->id]);

        return $this->findFor($user, $post);
    }

    public function unlike(User $user, Post $post): Post
    {
        $user->likes()
            ->where('post_id', $post->id)
            ->delete();

        return $this->findFor($user, $post);
    }

    /**
     * @return Collection<int, Comment>
     */
    public function commentsFor(Post $post): Collection
    {
        return $post->comments()
            ->with('user')
            ->oldest()
            ->get();
    }

    /**
     * @param  array{body: string}  $attributes
     */
    public function addComment(User $user, Post $post, array $attributes): Comment
    {
        return $user->comments()
            ->create([
                'post_id' => $post->id,
                'body' => $attributes['body'],
            ])
            ->load('user');
    }

    public function delete(Post $post): void
    {
        $imagePath = $post->image_path;

        if ($post->delete()) {
            $this->deleteImage($imagePath);
        }
    }

    private function postQuery(User $viewer): Builder
    {
        return Post::query()
            ->with([
                'user' => fn (BelongsTo $user) => $user->withCount([
                    'posts',
                    'followers',
                    'following',
                ]),
            ])
            ->withCount(['likes', 'comments'])
            ->withExists([
                'likes as is_liked' => fn (Builder $likes) => $likes
                    ->where('user_id', $viewer->id),
            ]);
    }

    private function storeImage(UploadedFile $image): string
    {
        $imagePath = $image->store('posts', 'public');

        if (! is_string($imagePath)) {
            throw new RuntimeException('Unable to store the post image.');
        }

        return $imagePath;
    }

    private function deleteImage(?string $imagePath): void
    {
        if ($imagePath === null || ! str_starts_with($imagePath, 'posts/')) {
            return;
        }

        Storage::disk('public')->delete($imagePath);
    }
}
