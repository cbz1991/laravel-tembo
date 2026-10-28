<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Resources\Skills\Marketplace;
use Cabuz\Tembo\Support\CursorPage;

/**
 * Organization and repository skills, plus the public skills marketplace.
 */
class Skills extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/skills', $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->tembo->post('v1/skills', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $skillId): array
    {
        return $this->tembo->get($this->path('v1/skills/%s', $skillId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $skillId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/skills/%s', $skillId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $skillId): array
    {
        return $this->tembo->delete($this->path('v1/skills/%s', $skillId));
    }

    public function marketplace(): Marketplace
    {
        return new Marketplace($this->tembo);
    }
}
