<?php

namespace Cabuz\Tembo\Live;

use Cabuz\Tembo\Exceptions\ConnectionException;

interface SocketFactory
{
    /**
     * Open a WebSocket. $timeout is in seconds and also applies to every
     * receive() call.
     *
     * @throws ConnectionException
     */
    public function connect(string $url, float $timeout): Socket;
}
