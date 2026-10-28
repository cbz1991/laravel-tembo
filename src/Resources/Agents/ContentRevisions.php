<?php

namespace Cabuz\Tembo\Resources\Agents;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

/**
 * Past versions of an agent's instructions.
 */
class ContentRevisions extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $agentId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/agents/%s/content-revisions', $agentId), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $agentId, int|string $version): array
    {
        return $this->tembo->get($this->path('v1/agents/%s/content-revisions/%s', $agentId, $version));
    }

    /**
     * Restore the instructions from an older revision. expectedContentVersion
     * is the agent's current contentVersion, which guards against overwriting
     * a concurrent edit.
     *
     * @return array<string, mixed>
     */
    public function restore(string $agentId, int|string $version, int $expectedContentVersion): array
    {
        return $this->tembo->post(
            $this->path('v1/agents/%s/content-revisions/%s/restore', $agentId, $version),
            ['expectedContentVersion' => $expectedContentVersion],
        );
    }
}
