<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'username' => $this->username,
            
            'email' => $this->when($request->user() && $request->user()->id === $this->id, $this->email),
            
            'bio' => $this->bio,
            
            'profile_photo_url' => $this->profile_photo_url,
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            'posts_count' => $this->posts_count ?? 0,
            'followers_count' => $this->followers_count ?? 0,
            'following_count' => $this->following_count ?? 0,
        ];
    }
}
