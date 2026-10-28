<?php

namespace Cabuz\Tembo\Resources\Projects;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Schedules extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $projectId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/projects/%s/schedules', $projectId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(string $projectId, array $data = []): array
    {
        return $this->tembo->post($this->path('v1/projects/%s/schedules', $projectId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $projectId, string $scheduleId): array
    {
        return $this->tembo->get($this->path('v1/projects/%s/schedules/%s', $projectId, $scheduleId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $projectId, string $scheduleId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/projects/%s/schedules/%s', $projectId, $scheduleId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $projectId, string $scheduleId): array
    {
        return $this->tembo->delete($this->path('v1/projects/%s/schedules/%s', $projectId, $scheduleId));
    }
}
