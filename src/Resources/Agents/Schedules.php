<?php

namespace Cabuz\Tembo\Resources\Agents;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Schedules extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $agentId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/agents/%s/schedules', $agentId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(string $agentId, array $data = []): array
    {
        return $this->tembo->post($this->path('v1/agents/%s/schedules', $agentId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $agentId, string $scheduleId): array
    {
        return $this->tembo->get($this->path('v1/agents/%s/schedules/%s', $agentId, $scheduleId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $agentId, string $scheduleId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/agents/%s/schedules/%s', $agentId, $scheduleId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $agentId, string $scheduleId): array
    {
        return $this->tembo->delete($this->path('v1/agents/%s/schedules/%s', $agentId, $scheduleId));
    }
}
