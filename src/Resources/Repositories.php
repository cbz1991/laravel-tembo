<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

class Repositories extends Resource
{
    /**
     * @param  array<string, mixed>  $query  search, ids, enabled, integrationId, sort, order, limit, cursor
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/repositories', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $repositoryId): array
    {
        return $this->tembo->get($this->path('v1/repositories/%s', $repositoryId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $repositoryId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/repositories/%s', $repositoryId), $data);
    }

    /**
     * Find a repository by "owner/name" (or just "name"). Returns null when
     * no enabled repository matches exactly.
     *
     * @return array<string, mixed>|null
     */
    public function findByName(string $name): ?array
    {
        $name = strtolower(trim($name));
        $search = str_contains($name, '/') ? substr($name, strrpos($name, '/') + 1) : $name;

        foreach ($this->list(['search' => $search, 'limit' => 100])->lazy() as $repository) {
            $repoName = strtolower((string) ($repository['name'] ?? ''));
            $fullName = strtolower((string) ($repository['owner'] ?? '')).'/'.$repoName;

            if ($name === $fullName || $name === $repoName) {
                return $repository;
            }
        }

        return null;
    }
}
