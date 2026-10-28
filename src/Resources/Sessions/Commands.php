<?php

namespace Cabuz\Tembo\Resources\Sessions;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Commands extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $sessionId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/sessions/%s/commands', $sessionId), $query);
    }
}
