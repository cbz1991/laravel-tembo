<?php

namespace Cabuz\Tembo\Resources;

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
    public function get(string $projectId): array
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

    /**
     * @param  array<string, mixed>  $query
     */
    public function listEnvironmentVariables(string $projectId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/projects/%s/environment-variables', $projectId), $query);
    }

    /**
     * Create or replace environment variables.
     *
     * @param  array<string, string>|list<array<string, mixed>>  $secrets  Either ['KEY' => 'value'] pairs or the raw API list.
     * @return array<string, mixed>
     */
    public function setEnvironmentVariables(string $projectId, array $secrets): array
    {
        if (! array_is_list($secrets)) {
            $secrets = array_map(
                static fn ($key, $value) => ['key' => (string) $key, 'value' => (string) $value],
                array_keys($secrets),
                $secrets,
            );
        }

        return $this->tembo->post($this->path('v1/projects/%s/environment-variables', $projectId), ['secrets' => $secrets]);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteEnvironmentVariable(string $projectId, string $key): array
    {
        return $this->tembo->delete($this->path('v1/projects/%s/environment-variables/%s', $projectId, $key));
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelBuild(string $projectId, string $buildId): array
    {
        return $this->tembo->post($this->path('v1/projects/%s/builds/%s/cancel', $projectId, $buildId));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listSnapshots(string $projectId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/projects/%s/snapshots', $projectId), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSnapshot(string $projectId, string $snapshotId): array
    {
        return $this->tembo->get($this->path('v1/projects/%s/snapshots/%s', $projectId, $snapshotId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateSnapshot(string $projectId, string $snapshotId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/projects/%s/snapshots/%s', $projectId, $snapshotId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteSnapshot(string $projectId, string $snapshotId): array
    {
        return $this->tembo->delete($this->path('v1/projects/%s/snapshots/%s', $projectId, $snapshotId));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listBuilds(string $projectId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/projects/%s/builds', $projectId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createBuild(string $projectId, array $data = []): array
    {
        return $this->tembo->post($this->path('v1/projects/%s/builds', $projectId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getBuild(string $projectId, string $buildId): array
    {
        return $this->tembo->get($this->path('v1/projects/%s/builds/%s', $projectId, $buildId));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listSchedules(string $projectId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/projects/%s/schedules', $projectId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createSchedule(string $projectId, array $data = []): array
    {
        return $this->tembo->post($this->path('v1/projects/%s/schedules', $projectId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchedule(string $projectId, string $scheduleId): array
    {
        return $this->tembo->get($this->path('v1/projects/%s/schedules/%s', $projectId, $scheduleId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateSchedule(string $projectId, string $scheduleId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/projects/%s/schedules/%s', $projectId, $scheduleId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteSchedule(string $projectId, string $scheduleId): array
    {
        return $this->tembo->delete($this->path('v1/projects/%s/schedules/%s', $projectId, $scheduleId));
    }
}
