<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

class Pins extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/pins', $query, 'resources');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->tembo->post('v1/pins', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $resourceType, string $resourceId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/pins/%s/%s', $resourceType, $resourceId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $resourceType, string $resourceId): array
    {
        return $this->tembo->delete($this->path('v1/pins/%s/%s', $resourceType, $resourceId));
    }
}
