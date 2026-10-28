<?php

namespace Cabuz\Tembo\Resources\Sessions;

use Cabuz\Tembo\Resources\Live;
use Cabuz\Tembo\Resources\Resource;
use Generator;

class EventsLive extends Resource
{
    /**
     * Stream a session's output as the agent produces it.
     *
     * @param  array{maxRetries?: int, initialDelay?: int, maxDelay?: int, timeout?: int|float}  $options
     * @return Generator<int, array<string, mixed>>
     *
     * @see Live::subscribe()
     */
    public function subscribe(string $sessionId, array $options = []): Generator
    {
        return $this->tembo->live()->subscribe(
            ['resource' => 'sessions.events', 'input' => ['scope' => ['sessionId' => $sessionId]]],
            $options,
        );
    }
}
