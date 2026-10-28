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

    /**
     * provider and idempotencyKey are required.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function reset(string $organizationId, array $data): array
    {
        return $this->tembo->post($this->path('v1/organizations/%s/subscriptions/reset', $organizationId), $data);
    }
}
