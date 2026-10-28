<?php

namespace Cabuz\Tembo\Live;

use Cabuz\Tembo\Exceptions\ConnectionException;
use Cabuz\Tembo\Exceptions\LiveSubscriptionException;

/**
 * A raw connection to Tembo's live API, for multiplexing several
 * subscriptions by hand. Most apps use Live::subscribe() instead.
 */
class LiveConnection
{
    public function __construct(protected Socket $socket) {}

    /**
     * Send a client message, e.g. ['op' => 'subscribe', 'id' => ..., 'resource' => ..., 'input' => ...].
     *
     * @param  array<string, mixed>  $message
     *
     * @throws ConnectionException
     */
    public function send(array $message): void
    {
        $this->socket->send((string) json_encode($message, JSON_THROW_ON_ERROR));
    }

    /**
     * The next server frame ("ready", "event" or "error"), or null when
     * nothing arrived within the timeout.
     *
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     * @throws LiveSubscriptionException on a malformed frame.
     */
    public function receive(): ?array
    {
        $text = $this->socket->receive();

        if ($text === null) {
            return null;
        }

        $frame = json_decode($text, true);

        if (! is_array($frame) || ! is_string($frame['type'] ?? null)) {
            throw new LiveSubscriptionException('Invalid live server frame.', 400);
        }

        return $frame;
    }

    public function close(): void
    {
        $this->socket->close();
    }
}
