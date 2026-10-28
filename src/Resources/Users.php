<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

class Users extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function me(): array
    {
        return $this->tembo->get('v1/users/me');
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $userId): array
    {
        return $this->tembo->get($this->path('v1/users/%s', $userId));
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $userId): array
    {
        return $this->tembo->delete($this->path('v1/users/%s', $userId));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function connectedAccounts(string $userId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/users/%s/connected-accounts', $userId), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function connectedAccount(string $userId, string $connectedAccountId): array
    {
        return $this->tembo->get($this->path('v1/users/%s/connected-accounts/%s', $userId, $connectedAccountId));
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(string $userId): array
    {
        return $this->tembo->get($this->path('v1/users/%s/settings', $userId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateSettings(string $userId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/users/%s/settings', $userId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function subscriptions(string $userId): array
    {
        return $this->tembo->get($this->path('v1/users/%s/subscriptions', $userId));
    }

    /**
     * @param  array<string, mixed>  $data  provider and idempotencyKey are required.
     * @return array<string, mixed>
     */
    public function resetSubscriptions(string $userId, array $data): array
    {
        return $this->tembo->post($this->path('v1/users/%s/subscriptions/reset', $userId), $data);
    }

    /**
     * The raw image bytes of the user's profile picture.
     */
    public function profilePicture(string $userId): string
    {
        return $this->tembo->send('GET', $this->path('v1/users/%s/profile-picture', $userId))->body();
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteProfilePicture(string $userId): array
    {
        return $this->tembo->delete($this->path('v1/users/%s/profile-picture', $userId));
    }
}
