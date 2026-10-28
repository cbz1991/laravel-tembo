<?php

namespace Cabuz\Tembo\Live;

use Cabuz\Tembo\Exceptions\ConnectionException;
use Cabuz\Tembo\Exceptions\MissingWebSocketClientException;
use WebSocket\Client;
use WebSocket\Exception\ExceptionInterface;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

/**
 * Opens sockets with phrity/websocket, an optional dependency.
 */
class PhritySocketFactory implements SocketFactory
{
    public function connect(string $url, float $timeout): Socket
    {
        if (! class_exists(Client::class)) {
            throw MissingWebSocketClientException::make();
        }

        try {
            $client = new Client($url);
            $client->setTimeout($timeout)
                ->addMiddleware(new CloseHandler)
                ->addMiddleware(new PingResponder)
                ->connect();
        } catch (ExceptionInterface $e) {
            throw new ConnectionException('Could not open the Tembo live connection: '.$e->getMessage(), 0, $e);
        }

        return new PhritySocket($client);
    }
}
