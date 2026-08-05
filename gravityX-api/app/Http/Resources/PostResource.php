<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caption' => $this->caption,
            'body' => $this->body,
            'image_url' => $this->imageUrl(),
            'user' => UserResource::make($this->whenLoaded('user')),
            'likes_count' => $this->likes_count ?? 0,
            'comments_count' => $this->comments_count ?? 0,
            'is_liked' => (bool) ($this->is_liked ?? false),
            'can_delete' => $request->user()?->id === $this->user_id,
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function imageUrl(): ?string
    {
        if ($this->image_mime_type !== null) {
            return '/media/posts/'.$this->id;
        }

        if ($this->image_path === null) {
            return null;
        }

        // Backward compatibility for records created before images were stored
        // in PostgreSQL. A relative URL prevents an incorrect APP_URL from
        // making the frontend request localhost.
        return '/storage/'.ltrim($this->image_path, '/');
    }
}
