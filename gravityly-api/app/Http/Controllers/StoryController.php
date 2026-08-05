<?php
namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Pest\Plugins\Tia\Storage;
use Illuminate\Support\Facades\Log;

class StoryController extends Controller {
    public function store(Request $request) 
    {
        $request-> validate([
            'media'=>'required|file|mimes:jpeg,png,jpg,mp4|max:10240'
        ]);

        $path = null;

        try {
            $file = $request->file('media');
            $path = $file->store('storie', 'public');
            $type = str_contains($file->getMimeType(), 'video') ? 'video' : 'image';

            $story = $request->user->story->create([
                'media_path' => $path,
                'media_type' => $type,
                'expires_at' => now()->addHour(24),
            ]);

            return response() -> json([
                'message' => 'Story created sucessfully',
                'story' => $story,
            ], 201);

        } catch (Exception $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            Log::error('Error on save story: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'An error occurred while creating the story. Please try again later.'
        ], 500);
    }
}