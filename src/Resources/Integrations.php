<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Resources\Integrations\Providers;
use Cabuz\Tembo\Resources\Integrations\Triggers;
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
    public function retrieve(string $integrationId): array
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
     * @param  array<string, mixed>  $query  returnUrl, capability
     * @return array<string, mixed>
     */
    public function authorize(string $integrationId, array $query = []): array
    {
        return $this->tembo->post($this->path('v1/integrations/%s/authorize', $integrationId), null, $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function test(string $integrationId): array
    {
        return $this->tembo->post($this->path('v1/integrations/%s/test', $integrationId));
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
     * Sync all eligible integrations.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function syncAll(array $data = []): array
    {
        return $this->tembo->post('v1/integrations/sync', $data);
    }

    /**
     * @param  array<string, mixed>  $query  integrationId
     * @return array<string, mixed>
     */
    public function retrieveSyncStatus(array $query = []): array
    {
        return $this->tembo->get('v1/integrations/sync/status', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveRateLimit(string $integrationId): array
    {
        return $this->tembo->get($this->path('v1/integrations/%s/rate-limit', $integrationId));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listSentryEnvironments(string $integrationId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/integrations/%s/sentry-environments', $integrationId), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listSlackChannels(string $integrationId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/integrations/%s/slack-channels', $integrationId), $query);
    }

    public function providers(): Providers
    {
        return new Providers($this->tembo);
    }

    public function triggers(): Triggers
    {
        return new Triggers($this->tembo);
    }
}
