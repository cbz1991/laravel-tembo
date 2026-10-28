<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Resources\Organizations\Members;
use Cabuz\Tembo\Resources\Organizations\Secrets;
use Cabuz\Tembo\Resources\Organizations\Settings;
use Cabuz\Tembo\Resources\Organizations\Subscriptions;

class Organizations extends Resource
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->tembo->post('v1/organizations', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $organizationId): array
    {
        return $this->tembo->get($this->path('v1/organizations/%s', $organizationId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $organizationId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/organizations/%s', $organizationId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $organizationId): array
    {
        return $this->tembo->delete($this->path('v1/organizations/%s', $organizationId));
    }

    public function members(): Members
    {
        return new Members($this->tembo);
    }

    public function secrets(): Secrets
    {
        return new Secrets($this->tembo);
    }

    public function settings(): Settings
    {
        return new Settings($this->tembo);
    }

    public function subscriptions(): Subscriptions
    {
        return new Subscriptions($this->tembo);
    }
}
