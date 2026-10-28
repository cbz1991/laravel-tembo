<?php

namespace Cabuz\Tembo\Events;

/**
 * Implemented by every event a watched session fires when it stops running
 * (SessionCompleted, SessionFailed, SessionCancelled, SessionWatchTimedOut).
 * Listen to this interface to handle every outcome in one place.
 *
 * @property-read string $sessionId
 * @property-read array<string, mixed> $session
 * @property-read array<string, mixed> $context
 */
interface SessionFinished
{
    public function state(): ?string;

    /**
     * @return list<string>
     */
    public function pullRequestUrls(): array;
}
