<?php

namespace Cabuz\Tembo\Testing;

use Cabuz\Tembo\Live\Socket;
use Cabuz\Tembo\Live\SocketFactory;
use PHPUnit\Framework\Assert;

/**
 * Stands in for Tembo's live API in tests; see Tembo::fakeLive().
 */
class FakeLive implements SocketFactory
{
    /**
     * Every subscribe message sent, across connections.
     *
     * @var list<array<string, mixed>>
     */
    public array $subscriptions = [];

    protected bool $connected = false;

    /**
     * @param  list<array<string, mixed>>  $deltas
     */
    public function __construct(protected array $deltas) {}

    public function connect(string $url, float $timeout): Socket
    {
        // Only the first connection replays the output; reconnects see a quiet stream.
        $deltas = $this->connected ? [] : $this->deltas;
        $this->connected = true;

        return new FakeLiveSocket($this, $deltas);
    }

    /**
     * Assert that a session's live output was subscribed to.
     */
    public function assertSubscribed(string $sessionId): void
    {
        Assert::assertContains(
            $sessionId,
            array_map(fn ($message) => $message['input']['scope']['sessionId'] ?? null, $this->subscriptions),
            "No live subscription to session [{$sessionId}].",
        );
    }
}
