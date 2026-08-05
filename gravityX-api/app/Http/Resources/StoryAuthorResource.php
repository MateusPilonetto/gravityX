<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryAuthorResource extends JsonResource
{
    /**
     * Transform an author and that author's active stories for the feed.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => UserResource::make($this->resource),
            'stories' => StoryResource::collection($this->whenLoaded('activeStories')),
        ];
    }
}
