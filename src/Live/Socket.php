<?php

namespace Cabuz\Tembo\Live;

use Cabuz\Tembo\Exceptions\ConnectionException;

/**
 * A connected WebSocket. Implement it (with a SocketFactory) to use another
 * WebSocket client, or to fake the live API in tests.
 */
interface Socket
{
    /**
     * @throws ConnectionException
     */
    public function send(string $text): void;

    /**
     * The next text frame, or null when nothing arrived within the timeout.
     *
     * @throws ConnectionException when the connection is closed or fails.
     */
    public function receive(): ?string;

    public function close(): void;
}
