<?php

namespace Cabuz\Tembo\Resources\Sessions;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Sources extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/sessions/sources', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $sourceId): array
    {
        return $this->tembo->get($this->path('v1/sessions/sources/%s', $sourceId));
    }
}
