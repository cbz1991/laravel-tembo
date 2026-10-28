<?php

namespace Cabuz\Tembo\Resources\Projects;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Builds extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $projectId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/projects/%s/builds', $projectId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(string $projectId, array $data = []): array
    {
        return $this->tembo->post($this->path('v1/projects/%s/builds', $projectId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $projectId, string $buildId): array
    {
        return $this->tembo->get($this->path('v1/projects/%s/builds/%s', $projectId, $buildId));
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(string $projectId, string $buildId): array
    {
        return $this->tembo->post($this->path('v1/projects/%s/builds/%s/cancel', $projectId, $buildId));
    }
}
