<?php

namespace Cabuz\Tembo\Resources\Organizations;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Members extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $organizationId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/organizations/%s/members', $organizationId), $query);
    }

    /**
     * Invite or add a member.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(string $organizationId, array $data): array
    {
        return $this->tembo->post($this->path('v1/organizations/%s/members', $organizationId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $organizationId, string $memberId): array
    {
        return $this->tembo->get($this->path('v1/organizations/%s/members/%s', $organizationId, $memberId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $organizationId, string $memberId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/organizations/%s/members/%s', $organizationId, $memberId), $data);
    }

    /**
     * Remove a member or revoke an invitation.
     *
     * @return array<string, mixed>
     */
    public function delete(string $organizationId, string $memberId): array
    {
        return $this->tembo->delete($this->path('v1/organizations/%s/members/%s', $organizationId, $memberId));
    }
}
