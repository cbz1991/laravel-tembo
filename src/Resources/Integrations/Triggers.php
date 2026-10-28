<?php

namespace Cabuz\Tembo\Resources\Integrations;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

/**
 * Integration events that agents can listen to.
 */
class Triggers extends Resource
{
    /**
     * Every trigger agents can listen to, across integrations.
     *
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/integrations/triggers', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $integrationId, string $triggerName): array
    {
        return $this->tembo->get($this->path('v1/integrations/%s/triggers/%s', $integrationId, $triggerName));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listFilterOptions(string $integrationId, string $triggerName, string $field, array $query = []): CursorPage
    {
        return $this->tembo->paginate(
            $this->path('v1/integrations/%s/triggers/%s/filter-options/%s', $integrationId, $triggerName, $field),
            $query,
        );
    }
}
