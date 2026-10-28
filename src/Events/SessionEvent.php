<?php

namespace Cabuz\Tembo\Events;

use Cabuz\Tembo\Support\SessionState;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Shared data and helpers of the session events.
 */
abstract class SessionEvent implements SessionFinished
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $session  The session as last returned by the API.
     * @param  array<string, mixed>  $context  The context passed to watch().
     */
    public function __construct(
        public string $sessionId,
        public array $session,
        public array $context = [],
    ) {}

    public function state(): ?string
    {
        return SessionState::of($this->session);
    }

    /**
     * Links to the pull requests the session opened, if any.
     *
     * @return list<string>
     */
    public function pullRequestUrls(): array
    {
        $urls = [];

        foreach ((array) ($this->session['artifacts'] ?? []) as $artifact) {
            foreach ((array) (is_array($artifact) ? ($artifact['pullRequests'] ?? []) : []) as $pullRequest) {
                if (is_array($pullRequest) && is_string($pullRequest['url'] ?? null)) {
                    $urls[] = $pullRequest['url'];
                }
            }
        }

        return array_values(array_unique($urls));
    }
}
