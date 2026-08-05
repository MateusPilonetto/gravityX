<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStoryRequest;
use App\Http\Resources\StoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StoryController extends Controller
{
    public function store(StoreStoryRequest $request): JsonResponse
    {
        /** @var UploadedFile $media */
        $media = $request->file('media');
        $path = null;

        try {
            $path = $media->store('stories', 'public');

            if (! is_string($path) || $path === '') {
                throw new RuntimeException('Unable to store the story media.');
            }

            $mediaType = $media->getMimeType() === 'video/mp4' ? 'video' : 'image';

            $story = $request->user()->stories()->create([
                'media_path' => $path,
                'media_type' => $mediaType,
                'expires_at' => now()->addHour(24),
            ]);

            return response()->json([
                'message' => 'Story created successfully.',
                'story' => new StoryResource($story),
            ], 201);
        } catch (Throwable $exception) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }

            report($exception);

            return response()->json([
                'message' => 'An error occurred while publishing the story. Please try again later.',
            ], 500);
        }
    }
}
