<?php

namespace Cabuz\Tembo\Resources;

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
    public function get(string $agentId): array
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
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function run(string $agentId, array $payload = []): array
    {
        return $this->createRun($agentId, $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function templates(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/agents/templates', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function template(string $templateName): array
    {
        return $this->tembo->get($this->path('v1/agents/templates/%s', $templateName));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listRevisions(string $agentId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/agents/%s/content-revisions', $agentId), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRevision(string $agentId, int|string $version): array
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
    public function restoreRevision(string $agentId, int|string $version, int $expectedContentVersion): array
    {
        return $this->tembo->post(
            $this->path('v1/agents/%s/content-revisions/%s/restore', $agentId, $version),
            ['expectedContentVersion' => $expectedContentVersion],
        );
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listSchedules(string $agentId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/agents/%s/schedules', $agentId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createSchedule(string $agentId, array $data = []): array
    {
        return $this->tembo->post($this->path('v1/agents/%s/schedules', $agentId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchedule(string $agentId, string $scheduleId): array
    {
        return $this->tembo->get($this->path('v1/agents/%s/schedules/%s', $agentId, $scheduleId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateSchedule(string $agentId, string $scheduleId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/agents/%s/schedules/%s', $agentId, $scheduleId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteSchedule(string $agentId, string $scheduleId): array
    {
        return $this->tembo->delete($this->path('v1/agents/%s/schedules/%s', $agentId, $scheduleId));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listTriggers(string $agentId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/agents/%s/triggers', $agentId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createTrigger(string $agentId, array $data = []): array
    {
        return $this->tembo->post($this->path('v1/agents/%s/triggers', $agentId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTrigger(string $agentId, string $triggerId): array
    {
        return $this->tembo->get($this->path('v1/agents/%s/triggers/%s', $agentId, $triggerId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateTrigger(string $agentId, string $triggerId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/agents/%s/triggers/%s', $agentId, $triggerId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteTrigger(string $agentId, string $triggerId): array
    {
        return $this->tembo->delete($this->path('v1/agents/%s/triggers/%s', $agentId, $triggerId));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listRuns(string $agentId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/agents/%s/runs', $agentId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createRun(string $agentId, array $data = []): array
    {
        return $this->tembo->post($this->path('v1/agents/%s/runs', $agentId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRun(string $agentId, string $runId): array
    {
        return $this->tembo->get($this->path('v1/agents/%s/runs/%s', $agentId, $runId));
    }
}
