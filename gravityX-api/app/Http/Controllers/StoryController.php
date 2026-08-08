<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStoryRequest;
use App\Http\Resources\StoryResource;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $path = $media->store('stories', 'local');

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
                Storage::disk('local')->delete($path);
            }

            report($exception);

            return response()->json([
                'message' => 'An error occurred while publishing the story. Please try again later.',
            ], 500);
        }
    }

    public function destroy(Request $request, Story $story): JsonResponse
    {
        if ($request->user()->id !== $story->user_id) {
            return response()->json([
                'message' => 'You are not allowed to delete this story.',
            ], 403);
        }

        $mediaPath = $story->media_path;
        $mediaDisk = $story->mediaDisk();

        $story->delete();

        try {
            Storage::disk($mediaDisk)->delete($mediaPath);
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'message' => 'Story deleted successfully.',
        ]);
    }
}
