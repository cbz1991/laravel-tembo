<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

/**
 * MCP servers that sessions and agents can use (pass their IDs as mcpServers).
 */
class McpConnections extends Resource
{
    /**
     * @param  array<string, mixed>  $query
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/mcp-connections', $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->tembo->post('v1/mcp-connections', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->tembo->get($this->path('v1/mcp-connections/%s', $id));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $id, array $data): array
    {
        return $this->tembo->patch($this->path('v1/mcp-connections/%s', $id), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->tembo->delete($this->path('v1/mcp-connections/%s', $id));
    }

    /**
     * @return array<string, mixed>
     */
    public function authorize(string $id): array
    {
        return $this->tembo->post($this->path('v1/mcp-connections/%s/authorize', $id));
    }

    /**
     * @return array<string, mixed>
     */
    public function test(string $id): array
    {
        return $this->tembo->post($this->path('v1/mcp-connections/%s/test', $id));
    }
}
