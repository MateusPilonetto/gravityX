<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function avatar(User $user): Response
    {
        return $this->imageResponse(
            $user->profile_photo_data,
            $user->profile_photo_mime_type,
            'private, no-cache, max-age=0'
        );
    }

    public function post(Post $post): Response
    {
        return $this->imageResponse(
            $post->image_data,
            $post->image_mime_type,
            'public, max-age=31536000, immutable'
        );
    }

    public function story(int $story): Response
    {
        $storyModel = Story::query()->findOrFail($story);

        if ($storyModel->expires_at === null || $storyModel->expires_at->isPast()) {
            abort(404);
        }

        if (! str_starts_with($storyModel->media_path, 'stories/')) {
            abort(404);
        }

        $disk = Storage::disk($storyModel->mediaDisk());

        if (! $disk->exists($storyModel->media_path)) {
            abort(404);
        }

        $mimeType = $disk->mimeType($storyModel->media_path);

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'video/mp4'], true)) {
            abort(404);
        }

        return new BinaryFileResponse(
            $disk->path($storyModel->media_path),
            200,
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
            false
        );
    }

    private function imageResponse(
        mixed $data,
        ?string $mimeType,
        string $cacheControl
    ): Response {
        if (is_resource($data)) {
            rewind($data);
            $data = stream_get_contents($data);
        }

        if (! is_string($data) || $data === '' || ! $this->isImageMimeType($mimeType)) {
            abort(404);
        }

        return response($data, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => (string) strlen($data),
            'Cache-Control' => $cacheControl,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function isImageMimeType(?string $mimeType): bool
    {
        return in_array($mimeType, [
            'image/jpeg',
            'image/png',
            'image/webp',
        ], true);
    }
}
