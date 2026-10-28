<?php

namespace Cabuz\Tembo\Resources\Agents;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Runs extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $agentId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/agents/%s/runs', $agentId), $query);
    }

    /**
     * Queue a run. The whole payload reaches the agent as event input.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(string $agentId, array $data = []): array
    {
        return $this->tembo->post($this->path('v1/agents/%s/runs', $agentId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $agentId, string $runId): array
    {
        return $this->tembo->get($this->path('v1/agents/%s/runs/%s', $agentId, $runId));
    }
}
