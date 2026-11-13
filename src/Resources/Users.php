<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Resources\Users\ConnectedAccounts;
use Cabuz\Tembo\Resources\Users\EnvironmentVariables;
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

    public function environmentVariables(): EnvironmentVariables
    {
        return new EnvironmentVariables($this->tembo);
    }

    public function profilePicture(): ProfilePicture
    {
        return new ProfilePicture($this->tembo);
    }
}
