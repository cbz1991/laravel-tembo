<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

class Models extends Resource
{
    /**
     * The agent and model keys you can pass as "agent", e.g. claudeCode:claude-fable-5-1.
     *
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/models', $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $modelName, array $data): array
    {
        return $this->tembo->patch($this->path('v1/models/%s', $modelName), $data);
    }
}
