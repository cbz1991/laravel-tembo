<?php

namespace Cabuz\Tembo\Resources\Skills;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Marketplace extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/skills/marketplace', $query);
    }

    /**
     * The query must include source.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function retrieve(string $skillId, array $query = []): array
    {
        return $this->tembo->get($this->path('v1/skills/marketplace/%s', $skillId), $query);
    }
}
