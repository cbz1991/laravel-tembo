<?php

namespace Cabuz\Tembo\Live;

use Cabuz\Tembo\Exceptions\ConnectionException;
use WebSocket\Client;
use WebSocket\Exception\ConnectionTimeoutException;
use WebSocket\Exception\ExceptionInterface;

class PhritySocket implements Socket
{
    public function __construct(protected Client $client) {}

    public function send(string $text): void
    {
        try {
            $this->client->text($text);
        } catch (ExceptionInterface $e) {
            throw new ConnectionException('Could not send to the Tembo live connection: '.$e->getMessage(), 0, $e);
        }
    }

    public function receive(): ?string
    {
        try {
            while (true) {
                $message = $this->client->receive();

                // Pings are answered by the middleware; a close ends the stream.
                if ($message->getOpcode() === 'text') {
                    return $message->getContent();
                }

                if ($message->getOpcode() === 'close' || ! $this->client->isConnected()) {
                    throw new ConnectionException('The Tembo live connection was closed.');
                }
            }
        } catch (ConnectionTimeoutException) {
            return null;
        } catch (ExceptionInterface $e) {
            throw new ConnectionException('The Tembo live connection failed: '.$e->getMessage(), 0, $e);
        }
    }

    public function close(): void
    {
        try {
            if ($this->client->isConnected()) {
                $this->client->close();
            }

            $this->client->disconnect();
        } catch (ExceptionInterface) {
            // Already gone.
        }
    }
}
