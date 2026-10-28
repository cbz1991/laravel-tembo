<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

/**
 * API keys of the authenticated user. The full token is only returned by create().
 */
class ApiKeys extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/api-keys', $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->tembo->post('v1/api-keys', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->tembo->get($this->path('v1/api-keys/%s', $id));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $id, array $data): array
    {
        return $this->tembo->patch($this->path('v1/api-keys/%s', $id), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->tembo->delete($this->path('v1/api-keys/%s', $id));
    }
}
