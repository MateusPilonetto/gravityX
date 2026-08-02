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

        $user->update([
            'name'     => $data['name'],
            'username' => $data['username'],
            'bio'      => $data['bio'] ?? null,
            ...(isset($data['profile_photo_url']) ? ['profile_photo_url' => $data['profile_photo_url']] : []),
        ]);

        return $user->fresh();
    }

    private function storeAvatar(UploadedFile $avatar): string
    {
        $path = $avatar->store('avatars', 'public');

        return Storage::disk('public')->url($path);
    }

    /**
     * Remove the previous avatar file from storage so replaced photos
     * don't pile up as orphaned files on disk.
     */
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
