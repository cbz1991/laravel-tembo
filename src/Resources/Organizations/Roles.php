<?php

namespace Cabuz\Tembo\Resources\Organizations;

use Cabuz\Tembo\Resources\Resource;

class Roles extends Resource
{
    /**
     * All roles at once; this list isn't paginated.
     *
     * @return list<array<string, mixed>>
     */
    public function list(string $organizationId): array
    {
        /** @var list<array<string, mixed>> $roles The endpoint returns a JSON array. */
        $roles = $this->tembo->get($this->path('v1/organizations/%s/roles', $organizationId));

        return $roles;
    }

    /**
     * @param  array<string, mixed>  $data  name and permissions are required; also description, icon, color.
     * @return array<string, mixed>
     */
    public function create(string $organizationId, array $data): array
    {
        return $this->tembo->post($this->path('v1/organizations/%s/roles', $organizationId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $organizationId, string $roleId): array
    {
        return $this->tembo->get($this->path('v1/organizations/%s/roles/%s', $organizationId, $roleId));
    }

    /**
     * @param  array<string, mixed>  $data  name, description, icon, color, permissions, isDefault
     * @return array<string, mixed>
     */
    public function update(string $organizationId, string $roleId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/organizations/%s/roles/%s', $organizationId, $roleId), $data);
    }

    /**
     * Archive a role; its members move to the replacement role.
     *
     * @return array<string, mixed>
     */
    public function delete(string $organizationId, string $roleId, string $replacementRoleId): array
    {
        return $this->tembo->delete($this->path('v1/organizations/%s/roles/%s', $organizationId, $roleId), [], ['replacementRoleId' => $replacementRoleId]);
    }
}
