<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Resources\Projects\Builds;
use Cabuz\Tembo\Resources\Projects\EnvironmentVariables;
use Cabuz\Tembo\Resources\Projects\Schedules;
use Cabuz\Tembo\Resources\Projects\Snapshots;
use Cabuz\Tembo\Support\CursorPage;

/**
 * Projects are workspace environments: repositories, setup script, sandbox
 * sizes, environment variables, snapshots, builds and schedules.
 */
class Projects extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/projects', $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data = []): array
    {
        return $this->tembo->post('v1/projects', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $projectId): array
    {
        return $this->tembo->get($this->path('v1/projects/%s', $projectId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $projectId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/projects/%s', $projectId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $projectId): array
    {
        return $this->tembo->delete($this->path('v1/projects/%s', $projectId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateDefaults(array $data): array
    {
        return $this->tembo->put('v1/projects/defaults', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function duplicate(string $projectId): array
    {
        return $this->tembo->post($this->path('v1/projects/%s/duplicate', $projectId));
    }

    public function environmentVariables(): EnvironmentVariables
    {
        return new EnvironmentVariables($this->tembo);
    }

    public function snapshots(): Snapshots
    {
        return new Snapshots($this->tembo);
    }

    public function builds(): Builds
    {
        return new Builds($this->tembo);
    }

    public function schedules(): Schedules
    {
        return new Schedules($this->tembo);
    }
}
