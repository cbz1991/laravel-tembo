<?php

namespace Cabuz\Tembo\Tests\Fakes;

use Cabuz\Tembo\Live\Socket;
use Cabuz\Tembo\Live\SocketFactory;

class FakeSocketFactory implements SocketFactory
{
    /** @var list<string> */
    public array $urls = [];

    /**
     * @param  list<FakeSocket>  $sockets  Handed out one per connect().
     */
    public function __construct(public array $sockets) {}

    public function connect(string $url, float $timeout): Socket
    {
        $this->urls[] = $url;

        return array_shift($this->sockets) ?? new FakeSocket([]);
    }
}
