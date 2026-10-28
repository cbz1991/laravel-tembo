<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Resources\Agents\ContentRevisions;
use Cabuz\Tembo\Resources\Agents\Runs;
use Cabuz\Tembo\Resources\Agents\Schedules;
use Cabuz\Tembo\Resources\Agents\Templates;
use Cabuz\Tembo\Resources\Agents\Triggers;
use Cabuz\Tembo\Support\CursorPage;

/**
 * Automation agents: reusable instructions that run on a schedule, on an
 * integration trigger (GitHub, Linear, Sentry, Slack...) or on demand.
 */
class Agents extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/agents', $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data = []): array
    {
        return $this->tembo->post('v1/agents', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $agentId): array
    {
        return $this->tembo->get($this->path('v1/agents/%s', $agentId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $agentId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/agents/%s', $agentId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $agentId): array
    {
        return $this->tembo->delete($this->path('v1/agents/%s', $agentId));
    }

    /**
     * Start an agent run. The payload is passed to the agent as custom input.
     * Shortcut for runs()->create().
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function run(string $agentId, array $payload = []): array
    {
        return $this->runs()->create($agentId, $payload);
    }

    public function templates(): Templates
    {
        return new Templates($this->tembo);
    }

    public function schedules(): Schedules
    {
        return new Schedules($this->tembo);
    }

    public function triggers(): Triggers
    {
        return new Triggers($this->tembo);
    }

    public function runs(): Runs
    {
        return new Runs($this->tembo);
    }

    public function contentRevisions(): ContentRevisions
    {
        return new ContentRevisions($this->tembo);
    }
}
