<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

class Billing extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function retrieve(): array
    {
        return $this->tembo->get('v1/billing');
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function listUsage(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/billing/usage', $query);
    }
}
