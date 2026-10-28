<?php

namespace Cabuz\Tembo\Testing;

use Cabuz\Tembo\Live\Socket;

/**
 * Speaks the live protocol: connection ready, subscription ready, the given
 * output events, then silence (read timeouts) until the caller stops.
 */
class FakeLiveSocket implements Socket
{
    /**
     * How many read timeouts to allow after the output runs out, so a loop
     * that never breaks fails instead of hanging the test suite.
     */
    public const MAX_IDLE = 100;

    /** @var list<array<string, mixed>> */
    protected array $frames = [['type' => 'ready']];

    protected int $idle = 0;

    /**
     * @param  list<array<string, mixed>>  $deltas
     */
    public function __construct(protected FakeLive $live, protected array $deltas) {}

    public function send(string $text): void
    {
        $message = json_decode($text, true);

        if (! is_array($message) || ($message['op'] ?? null) !== 'subscribe') {
            return;
        }

        $this->live->subscriptions[] = $message;
        $this->frames[] = ['type' => 'ready', 'id' => $message['id']];

        foreach ($this->deltas as $delta) {
            $this->frames[] = ['type' => 'event', 'id' => $message['id'], 'data' => $delta];
        }
    }

    public function receive(): ?string
    {
        $frame = array_shift($this->frames);

        if ($frame !== null) {
            return (string) json_encode($frame);
        }

        if (++$this->idle > self::MAX_IDLE) {
            return (string) json_encode([
                'type' => 'error',
                'id' => 'events',
                'status' => 400,
                'message' => 'The fake live stream has no more output; break out of the loop.',
            ]);
        }

        return null;
    }

    public function close(): void {}
}
