<?php

namespace Cabuz\Tembo\Resources\Users;

use Cabuz\Tembo\Resources\Resource;

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
     * @return array<string, mixed>
     */
    public function delete(string $userId): array
    {
        return $this->tembo->delete($this->path('v1/users/%s/profile-picture', $userId));
    }
}
