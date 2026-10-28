<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

/**
 * Connected services (GitHub, GitLab, Linear, Jira, Sentry, Slack, Snyk...).
 */
class Integrations extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/integrations', $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data = []): array
    {
        return $this->tembo->post('v1/integrations', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $integrationId): array
    {
        return $this->tembo->get($this->path('v1/integrations/%s', $integrationId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $integrationId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/integrations/%s', $integrationId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $integrationId): array
    {
        return $this->tembo->delete($this->path('v1/integrations/%s', $integrationId));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function providers(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/integrations/providers', $query);
    }

    /**
     * Every trigger agents can listen to, across integrations.
     *
     * @param  array<string, mixed>  $query
     */
    public function triggers(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/integrations/triggers', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function trigger(string $integrationId, string $triggerName): array
    {
        return $this->tembo->get($this->path('v1/integrations/%s/triggers/%s', $integrationId, $triggerName));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function triggerFilterOptions(string $integrationId, string $triggerName, string $field, array $query = []): CursorPage
    {
        return $this->tembo->paginate(
            $this->path('v1/integrations/%s/triggers/%s/filter-options/%s', $integrationId, $triggerName, $field),
            $query,
        );
    }

    /**
     * Sync all eligible integrations.
     *
     * @return array<string, mixed>
     */
    public function syncAll(): array
    {
        return $this->tembo->post('v1/integrations/sync', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function syncStatus(): array
    {
        return $this->tembo->get('v1/integrations/sync/status');
    }

    /**
     * Sync one integration.
     *
     * @param  array<string, mixed>  $data  mode
     * @return array<string, mixed>
     */
    public function sync(string $integrationId, array $data = []): array
    {
        return $this->tembo->post($this->path('v1/integrations/%s/sync', $integrationId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function authorize(string $integrationId): array
    {
        return $this->tembo->post($this->path('v1/integrations/%s/authorize', $integrationId));
    }

    /**
     * @return array<string, mixed>
     */
    public function test(string $integrationId): array
    {
        return $this->tembo->post($this->path('v1/integrations/%s/test', $integrationId));
    }

    /**
     * @return array<string, mixed>
     */
    public function rateLimit(string $integrationId): array
    {
        return $this->tembo->get($this->path('v1/integrations/%s/rate-limit', $integrationId));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function sentryEnvironments(string $integrationId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/integrations/%s/sentry-environments', $integrationId), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function slackChannels(string $integrationId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/integrations/%s/slack-channels', $integrationId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function discoverSnykOrganizations(array $data): array
    {
        return $this->tembo->post('v1/integrations/providers/snyk/organizations', $data);
    }
}
