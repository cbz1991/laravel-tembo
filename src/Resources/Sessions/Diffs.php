<?php

namespace Cabuz\Tembo\Resources\Sessions;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Diffs extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $sessionId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/sessions/%s/diffs', $sessionId), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $sessionId, string $diffId): array
    {
        return $this->tembo->get($this->path('v1/sessions/%s/diffs/%s', $sessionId, $diffId));
    }
}
