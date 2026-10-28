<?php

namespace Cabuz\Tembo\Resources\Organizations;

use Cabuz\Tembo\Resources\Resource;

class Settings extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $organizationId): array
    {
        return $this->tembo->get($this->path('v1/organizations/%s/settings', $organizationId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $organizationId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/organizations/%s/settings', $organizationId), $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateCredentials(string $organizationId, array $data): array
    {
        return $this->tembo->post($this->path('v1/organizations/%s/settings/validate-credentials', $organizationId), $data);
    }
}
