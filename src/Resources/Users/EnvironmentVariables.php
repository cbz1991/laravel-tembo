<?php

namespace Cabuz\Tembo\Resources\Users;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

/**
 * Personal environment variables, injected into every session the user starts
 * in the current organization. Values are never returned.
 */
class EnvironmentVariables extends Resource
{
    public function list(string $userId): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/users/%s/environment-variables', $userId));
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $userId, string $key, string $value): array
    {
        return $this->tembo->post($this->path('v1/users/%s/environment-variables', $userId), ['key' => $key, 'value' => $value]);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $userId, string $environmentVariableId): array
    {
        return $this->tembo->delete($this->path('v1/users/%s/environment-variables/%s', $userId, $environmentVariableId));
    }
}
