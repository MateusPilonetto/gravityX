<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function update(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        if ($avatar) {
            $this->deleteCurrentAvatar($user);
            $data['profile_photo_url'] = $this->storeAvatar($avatar);
        }

        $user->update($data);

        return $user;
    }

    private function storeAvatar(UploadedFile $avatar): string
    {
        $path = $avatar->store('avatars', 'public');

        return '/storage/'.$path;
    }

    private function deleteCurrentAvatar(User $user): void
    {
        if (! $user->profile_photo_url) {
            return;
        }

        $currentPath = parse_url($user->profile_photo_url, PHP_URL_PATH) ?? $user->profile_photo_url;
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
