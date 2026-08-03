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

        return Storage::disk('public')->url($path);
    }

    private function deleteCurrentAvatar(User $user): void
    {
        if (! $user->profile_photo_url) {
            return;
        }

        $publicUrlPrefix = Storage::disk('public')->url('');
        $currentPath = str_replace($publicUrlPrefix, '', $user->profile_photo_url);

        if ($currentPath && Storage::disk('public')->exists($currentPath)) {
            Storage::disk('public')->delete($currentPath);
        }
    }
}
