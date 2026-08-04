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
use RuntimeException;

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
        if ($image !== null) {
            $attributes = [
                ...$attributes,
                ...$this->imageAttributes($image),
            ];
        }

        $post = $user->posts()->create($attributes);

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
        $post->delete();
    }

    private function postQuery(User $viewer): Builder
    {
        return Post::query()
            // Image bytes are fetched only by MediaController. Keeping them out
            // of feed/detail queries avoids loading up to 5 MB per post.
            ->select([
                'posts.id',
                'posts.user_id',
                'posts.caption',
                'posts.body',
                'posts.image_path',
                'posts.image_mime_type',
                'posts.created_at',
                'posts.updated_at',
            ])
            ->with([
                'user' => fn (BelongsTo $user) => $user
                    ->select([
                        'users.id',
                        'users.name',
                        'users.username',
                        'users.email',
                        'users.bio',
                        'users.profile_photo_url',
                        'users.created_at',
                        'users.updated_at',
                    ])
                    ->withCount([
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

    /**
     * @return array{image_data: resource, image_mime_type: string}
     */
    private function imageAttributes(UploadedFile $image): array
    {
        $data = $image->get();

        if (! is_string($data) || $data === '') {
            throw new RuntimeException('Unable to read the post image.');
        }

        $mimeType = $image->getMimeType();

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('The post image has an unsupported MIME type.');
        }

        return [
            'image_data' => $this->databaseBlob($data),
            'image_mime_type' => $mimeType,
        ];
    }

    /**
     * Use a LOB binding so bytea is written correctly by PDO_PGSQL.
     *
     * @return resource
     */
    private function databaseBlob(string $data)
    {
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false || fwrite($stream, $data) !== strlen($data)) {
            throw new RuntimeException('Unable to prepare the post image for storage.');
        }

        rewind($stream);

        return $stream;
    }
}
