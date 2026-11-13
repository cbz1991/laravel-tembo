<?php

namespace Cabuz\Tembo\Resources\Organizations;

use Cabuz\Tembo\Resources\Resource;

class Subscriptions extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $organizationId): array
    {
        return $this->tembo->get($this->path('v1/organizations/%s/subscriptions', $organizationId));
    }
}
