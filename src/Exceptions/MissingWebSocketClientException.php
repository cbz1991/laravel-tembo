<?php

namespace Cabuz\Tembo\Exceptions;

class MissingWebSocketClientException extends TemboException
{
    public static function make(): self
    {
        return new self('Live streaming needs a WebSocket client. Run: composer require phrity/websocket');
    }
}
