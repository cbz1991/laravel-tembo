<?php

namespace Cabuz\Tembo\Resources\Users;

use Cabuz\Tembo\Resources\Resource;

class Subscriptions extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $userId): array
    {
        return $this->tembo->get($this->path('v1/users/%s/subscriptions', $userId));
    }
}
