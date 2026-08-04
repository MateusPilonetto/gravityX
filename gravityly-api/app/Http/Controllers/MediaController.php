<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Response;

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
