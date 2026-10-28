<?php

namespace Cabuz\Tembo\Resources\Sessions;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Inputs extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(string $sessionId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/sessions/%s/inputs', $sessionId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $sessionId, string $inputId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/sessions/%s/inputs/%s', $sessionId, $inputId), $data);
    }
}
