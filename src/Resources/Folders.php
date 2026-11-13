<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Support\CursorPage;

/**
 * The sidebar folders you use to organize sessions. List a folder's sessions
 * with sessions()->list(['folderId' => $folderId]).
 */
class Folders extends Resource
{
    public function list(): CursorPage
    {
        return $this->tembo->paginate('v1/folders');
    }

    /**
     * @param  array<string, mixed>  $data  name is required; also color.
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->tembo->post('v1/folders', $data);
    }

    /**
     * Rename, recolor or move a folder, or add or remove its sessions.
     *
     * @param  array<string, mixed>  $data  name, color, placement, addSession, removeSession
     * @return array<string, mixed>
     */
    public function update(string $folderId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/folders/%s', $folderId), $data);
    }

    /**
     * Delete a folder. Its sessions are kept.
     *
     * @return array<string, mixed>
     */
    public function delete(string $folderId): array
    {
        return $this->tembo->delete($this->path('v1/folders/%s', $folderId));
    }
}
