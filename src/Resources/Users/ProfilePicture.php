<?php

namespace Cabuz\Tembo\Resources\Users;

use Cabuz\Tembo\Resources\Resource;
use Illuminate\Http\UploadedFile;
use SplFileInfo;

class ProfilePicture extends Resource
{
    /**
     * The raw image bytes of the user's profile picture.
     */
    public function retrieve(string $userId): string
    {
        return $this->tembo->send('GET', $this->path('v1/users/%s/profile-picture', $userId))->body();
    }

    /**
     * Upload a new profile picture.
     *
     * @param  SplFileInfo|resource|string  $file  An uploaded file, a file object, a stream, or the raw image bytes.
     * @param  string|null  $filename  Defaults to the file's own name.
     * @return array<string, mixed>
     */
    public function update(string $userId, mixed $file, ?string $filename = null): array
    {
        if ($file instanceof SplFileInfo) {
            $filename ??= $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
            $contents = fopen($file->getRealPath() ?: $file->getPathname(), 'r');
        } else {
            $contents = $file;
        }

        return $this->tembo->upload('PUT', $this->path('v1/users/%s/profile-picture', $userId), [
            ['name' => 'file', 'contents' => $contents, 'filename' => $filename ?? 'profile-picture'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $userId): array
    {
        return $this->tembo->delete($this->path('v1/users/%s/profile-picture', $userId));
    }
}
