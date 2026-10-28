<?php

namespace Cabuz\Tembo\Resources\Users;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class ConnectedAccounts extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $userId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/users/%s/connected-accounts', $userId), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $userId, string $connectedAccountId): array
    {
        return $this->tembo->get($this->path('v1/users/%s/connected-accounts/%s', $userId, $connectedAccountId));
    }
}
