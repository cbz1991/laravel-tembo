<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Resources\Users\ConnectedAccounts;
use Cabuz\Tembo\Resources\Users\ProfilePicture;
use Cabuz\Tembo\Resources\Users\Settings;
use Cabuz\Tembo\Resources\Users\Subscriptions;

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
    public function retrieve(string $userId): array
    {
        return $this->tembo->get($this->path('v1/users/%s', $userId));
    }

    /**
     * @param  array<string, mixed>  $data  deletionQuestionnaire
     * @return array<string, mixed>
     */
    public function delete(string $userId, array $data = []): array
    {
        return $this->tembo->delete($this->path('v1/users/%s', $userId), [], $data);
    }

    public function settings(): Settings
    {
        return new Settings($this->tembo);
    }

    public function connectedAccounts(): ConnectedAccounts
    {
        return new ConnectedAccounts($this->tembo);
    }

    public function subscriptions(): Subscriptions
    {
        return new Subscriptions($this->tembo);
    }

    public function profilePicture(): ProfilePicture
    {
        return new ProfilePicture($this->tembo);
    }
}
