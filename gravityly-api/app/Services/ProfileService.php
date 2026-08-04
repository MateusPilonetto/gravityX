<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class ProfileService
{
    public function update(User $user, array $attributes, ?UploadedFile $avatar = null): User
    {
        unset($attributes['avatar']);

        if ($avatar !== null) {
            $attributes = [
                ...$attributes,
                ...$this->avatarAttributes($avatar),
                'profile_photo_url' => '/media/avatars/'.$user->id,
            ];
        }

        $user->update($attributes);

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

        if (! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            throw new RuntimeException('The profile photo has an unsupported MIME type.');
        }

        return [
            'profile_photo_data' => $this->databaseBlob($data),
            'profile_photo_mime_type' => $mimeType,
        ];
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
