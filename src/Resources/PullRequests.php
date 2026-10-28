<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

class PullRequests extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/pull-requests', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $pullRequestId): array
    {
        return $this->tembo->get($this->path('v1/pull-requests/%s', $pullRequestId));
    }
}
