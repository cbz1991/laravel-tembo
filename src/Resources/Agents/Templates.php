<?php

namespace Cabuz\Tembo\Resources\Agents;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

/**
 * Starter templates for automation agents.
 */
class Templates extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/agents/templates', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $templateName): array
    {
        return $this->tembo->get($this->path('v1/agents/templates/%s', $templateName));
    }
}
