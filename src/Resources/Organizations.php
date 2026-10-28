<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

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
    public function get(string $organizationId): array
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

    // Members ----------------------------------------------------------

    /**
     * @param  array<string, mixed>  $query
     */
    public function listMembers(string $organizationId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/organizations/%s/members', $organizationId), $query);
    }

    /**
     * Invite or add a member.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createMember(string $organizationId, array $data): array
    {
        return $this->tembo->post($this->path('v1/organizations/%s/members', $organizationId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMember(string $organizationId, string $memberId): array
    {
        return $this->tembo->get($this->path('v1/organizations/%s/members/%s', $organizationId, $memberId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateMember(string $organizationId, string $memberId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/organizations/%s/members/%s', $organizationId, $memberId), $data);
    }

    /**
     * Remove a member or revoke an invitation.
     *
     * @return array<string, mixed>
     */
    public function deleteMember(string $organizationId, string $memberId): array
    {
        return $this->tembo->delete($this->path('v1/organizations/%s/members/%s', $organizationId, $memberId));
    }

    // Secrets ----------------------------------------------------------

    /**
     * @param  array<string, mixed>  $query
     */
    public function listSecrets(string $organizationId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/organizations/%s/secrets', $organizationId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createSecret(string $organizationId, array $data): array
    {
        return $this->tembo->post($this->path('v1/organizations/%s/secrets', $organizationId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSecret(string $organizationId, string $secretId): array
    {
        return $this->tembo->get($this->path('v1/organizations/%s/secrets/%s', $organizationId, $secretId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateSecret(string $organizationId, string $secretId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/organizations/%s/secrets/%s', $organizationId, $secretId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteSecret(string $organizationId, string $secretId): array
    {
        return $this->tembo->delete($this->path('v1/organizations/%s/secrets/%s', $organizationId, $secretId));
    }

    // Settings & subscriptions -----------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function settings(string $organizationId): array
    {
        return $this->tembo->get($this->path('v1/organizations/%s/settings', $organizationId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateSettings(string $organizationId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/organizations/%s/settings', $organizationId), $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateCredentials(string $organizationId, array $data): array
    {
        return $this->tembo->post($this->path('v1/organizations/%s/settings/validate-credentials', $organizationId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function subscriptions(string $organizationId): array
    {
        return $this->tembo->get($this->path('v1/organizations/%s/subscriptions', $organizationId));
    }

    /**
     * @param  array<string, mixed>  $data  provider and idempotencyKey are required.
     * @return array<string, mixed>
     */
    public function resetSubscriptions(string $organizationId, array $data): array
    {
        return $this->tembo->post($this->path('v1/organizations/%s/subscriptions/reset', $organizationId), $data);
    }
}
