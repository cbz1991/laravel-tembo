<?php

namespace Cabuz\Tembo\Resources\Projects;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Snapshots extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $projectId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/projects/%s/snapshots', $projectId), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $projectId, string $snapshotId): array
    {
        return $this->tembo->get($this->path('v1/projects/%s/snapshots/%s', $projectId, $snapshotId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $projectId, string $snapshotId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/projects/%s/snapshots/%s', $projectId, $snapshotId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $projectId, string $snapshotId): array
    {
        return $this->tembo->delete($this->path('v1/projects/%s/snapshots/%s', $projectId, $snapshotId));
    }
}
