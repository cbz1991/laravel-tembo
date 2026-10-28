<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

class Insights extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(array $query = []): array
    {
        return $this->tembo->get('v1/insights', $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(array $data): array
    {
        return $this->tembo->patch('v1/insights', $data);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function members(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/insights/members', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function repositories(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/insights/repositories', $query);
    }
}
