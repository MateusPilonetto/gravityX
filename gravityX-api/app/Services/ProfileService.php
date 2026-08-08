<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProfileService
{
    public function update(User $user, array $attributes, ?UploadedFile $avatar = null): User
    {
        $legacyAvatarPath = $avatar !== null
            ? $this->legacyAvatarPath($user->profile_photo_url)
            : null;

        unset($attributes['avatar']);

        if ($avatar !== null) {
            $attributes = [
                ...$attributes,
                ...$this->avatarAttributes($avatar),
                'profile_photo_url' => '/media/avatars/'.$user->id,
            ];
        }

        $user->update($attributes);

        if ($legacyAvatarPath !== null) {
            try {
                Storage::disk('public')->delete($legacyAvatarPath);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $user;
    }

    /**
     * @return array{profile_photo_data: resource, profile_photo_mime_type: string}
     */
    private function avatarAttributes(UploadedFile $avatar): array
    {
        $data = $avatar->get();

        if (! is_string($data) || $data === '') {
            throw new RuntimeException('Unable to read the profile photo.');
        }

        $mimeType = $avatar->getMimeType();

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('The profile photo has an unsupported MIME type.');
        }

        return [
            'profile_photo_data' => $this->databaseBlob($data),
            'profile_photo_mime_type' => $mimeType,
        ];
    }

    private function legacyAvatarPath(?string $profilePhotoUrl): ?string
    {
        if (! is_string($profilePhotoUrl) || $profilePhotoUrl === '') {
            return null;
        }

        $normalizedPath = ltrim($profilePhotoUrl, '/');

        if (str_starts_with($normalizedPath, 'storage/')) {
            $normalizedPath = substr($normalizedPath, strlen('storage/'));
        }

        return str_starts_with($normalizedPath, 'avatars/') ? $normalizedPath : null;
    }

    /**
     * Use a LOB binding so bytea is written correctly by PDO_PGSQL.
     *
     * @return resource
     */
    private function databaseBlob(string $data)
    {
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false || fwrite($stream, $data) !== strlen($data)) {
            throw new RuntimeException('Unable to prepare the profile photo for storage.');
        }

        rewind($stream);

        return $stream;
    }
}
