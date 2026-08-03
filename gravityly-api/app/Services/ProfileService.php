<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function update(User $user, array $attributes, ?UploadedFile $avatar = null): User
    {
        $previousAvatarUrl = $user->profile_photo_url;
        unset($attributes['avatar']);

        if ($avatar !== null) {
            $attributes['profile_photo_url'] = $this->storeAvatar($avatar);
        }

        $user->update($attributes);

        if ($avatar !== null && $previousAvatarUrl !== null) {
            $this->deleteAvatar($previousAvatarUrl);
        }

        return $user;
    }

    private function storeAvatar(UploadedFile $avatar): string
    {
        $path = $avatar->store('avatars', 'public');

        return '/storage/'.$path;
    }

    private function deleteAvatar(string $profilePhotoUrl): void
    {
        $urlPath = parse_url($profilePhotoUrl, PHP_URL_PATH);
        $currentPath = is_string($urlPath) ? $urlPath : $profilePhotoUrl;
        $currentPath = ltrim($currentPath, '/');

        $publicUrlPath = parse_url(Storage::disk('public')->url(''), PHP_URL_PATH) ?? '/storage';
        $publicUrlPath = trim($publicUrlPath, '/');

        if (str_starts_with($currentPath, $publicUrlPath.'/')) {
            $currentPath = substr($currentPath, strlen($publicUrlPath) + 1);
        }

        if (str_starts_with($currentPath, 'avatars/') && Storage::disk('public')->exists($currentPath)) {
            Storage::disk('public')->delete($currentPath);
        }
    }
}
