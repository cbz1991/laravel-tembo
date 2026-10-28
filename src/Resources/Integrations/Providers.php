<?php

namespace Cabuz\Tembo\Resources\Integrations;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Support\CursorPage;

class Providers extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/integrations/providers', $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function discoverSnykOrganizations(array $data): array
    {
        return $this->tembo->post('v1/integrations/providers/snyk/organizations', $data);
    }
}
