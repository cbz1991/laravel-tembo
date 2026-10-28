<?php

namespace Cabuz\Tembo\Resources\Sessions;

use Cabuz\Tembo\Resources\Resource;

class Queue extends Resource
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $sessionId, array $data): array
    {
        return $this->tembo->put($this->path('v1/sessions/%s/queue', $sessionId), $data);
    }
}
