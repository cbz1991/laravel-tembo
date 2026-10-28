<?php

namespace Cabuz\Tembo\Resources\Users;

use Cabuz\Tembo\Resources\Resource;

class Settings extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $userId): array
    {
        return $this->tembo->get($this->path('v1/users/%s/settings', $userId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $userId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/users/%s/settings', $userId), $data);
    }
}
