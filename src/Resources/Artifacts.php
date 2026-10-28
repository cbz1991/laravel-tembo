<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

class Artifacts extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/artifacts', $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->tembo->post('v1/artifacts', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $artifactId): array
    {
        return $this->tembo->get($this->path('v1/artifacts/%s', $artifactId));
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $artifactId): array
    {
        return $this->tembo->delete($this->path('v1/artifacts/%s', $artifactId));
    }
}
