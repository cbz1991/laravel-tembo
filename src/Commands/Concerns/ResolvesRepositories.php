<?php

namespace Cabuz\Tembo\Commands\Concerns;

use Cabuz\Tembo\Tembo;
use Illuminate\Support\Str;

trait ResolvesRepositories
{
    /**
     * Repository IDs for a list of IDs or owner/name pairs.
     *
     * @param  array<mixed>  $repositories
     * @return list<string>|null null when a repository name couldn't be resolved.
     */
    protected function repositoryIds(Tembo $tembo, array $repositories): ?array
    {
        $ids = [];

        foreach ($repositories as $repo) {
            $repo = (string) $repo;

            if (Str::isUuid($repo)) {
                $ids[] = $repo;

                continue;
            }

            $repository = $tembo->repositories()->findByName($repo);

            if ($repository === null || ! is_string($repository['id'] ?? null)) {
                $this->components->error("Repository [{$repo}] was not found in Tembo.");

                return null;
            }

            $ids[] = $repository['id'];
        }

        return $ids;
    }
}
