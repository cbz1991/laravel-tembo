<?php

namespace Cabuz\Tembo\Tests\Fakes;

use Cabuz\Tembo\Exceptions\ConnectionException;
use Cabuz\Tembo\Live\Socket;

class FakeSocket implements Socket
{
    /** @var list<array<string, mixed>> */
    public array $sent = [];

    public bool $closed = false;

    /**
     * @param  list<array<string, mixed>|null>  $frames  Frames to receive; null is a read timeout. The connection drops after the last one.
     */
    public function __construct(public array $frames) {}

    public function send(string $text): void
    {
        $this->sent[] = json_decode($text, true);
    }

    public function receive(): ?string
    {
        if ($this->frames === []) {
            throw new ConnectionException('The Tembo live connection was closed.');
        }

        $frame = array_shift($this->frames);

        return $frame === null ? null : (string) json_encode($frame);
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
