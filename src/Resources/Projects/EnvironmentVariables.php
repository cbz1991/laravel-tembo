<?php

namespace Cabuz\Tembo\Resources\Projects;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class EnvironmentVariables extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $projectId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/projects/%s/environment-variables', $projectId), $query);
    }

    /**
     * Create or replace environment variables.
     *
     * @param  array<string, string>|list<array<string, mixed>>  $secrets  Either ['KEY' => 'value'] pairs or the raw API list.
     * @return array<string, mixed>
     */
    public function create(string $projectId, array $secrets): array
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
    public function delete(string $projectId, string $key): array
    {
        return $this->tembo->delete($this->path('v1/projects/%s/environment-variables/%s', $projectId, $key));
    }
}
