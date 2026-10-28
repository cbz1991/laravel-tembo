<?php

namespace Cabuz\Tembo\Resources\Organizations;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Secrets extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $organizationId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/organizations/%s/secrets', $organizationId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(string $organizationId, array $data): array
    {
        return $this->tembo->post($this->path('v1/organizations/%s/secrets', $organizationId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $organizationId, string $secretId): array
    {
        return $this->tembo->get($this->path('v1/organizations/%s/secrets/%s', $organizationId, $secretId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $organizationId, string $secretId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/organizations/%s/secrets/%s', $organizationId, $secretId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $organizationId, string $secretId): array
    {
        return $this->tembo->delete($this->path('v1/organizations/%s/secrets/%s', $organizationId, $secretId));
    }
}
