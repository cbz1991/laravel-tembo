<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

class Messages extends Resource
{
    /**
     * @param  array<string, mixed>  $query  sessionId, threadId, limit, cursor
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/messages', $query);
    }

    /**
     * Send a task, follow-up or steering instruction to a session.
     *
     * @param  array<string, mixed>  $data  sessionId and content are required.
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->tembo->post('v1/messages', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $messageId): array
    {
        return $this->tembo->get($this->path('v1/messages/%s', $messageId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $messageId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/messages/%s', $messageId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $messageId): array
    {
        return $this->tembo->delete($this->path('v1/messages/%s', $messageId));
    }
}
