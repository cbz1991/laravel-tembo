<?php

namespace Cabuz\Tembo\Support;

/**
 * Reads the state of a session returned by the API.
 */
final class SessionState
{
    public const QUEUED = 'queued';

    public const IN_PROGRESS = 'inProgress';

    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    /**
     * The current state of a session, or null when the API didn't say.
     *
     * @param  array<string, mixed>  $session
     */
    public static function of(array $session): ?string
    {
        $state = $session['state'] ?? null;

        if (! is_array($state)) {
            return null;
        }

        if (isset($state['current']) && is_string($state['current'])) {
            return $state['current'];
        }

        return match (true) {
            ($state['isCompleted'] ?? false) === true => self::COMPLETED,
            ($state['isFailed'] ?? false) === true => self::FAILED,
            ($state['isCancelled'] ?? false) === true => self::CANCELLED,
            ($state['inProgress'] ?? false) === true => self::IN_PROGRESS,
            ($state['isQueued'] ?? false) === true => self::QUEUED,
            default => null,
        };
    }

    /**
     * Whether the session completed, failed or was cancelled.
     *
     * @param  array<string, mixed>  $session
     */
    public static function isFinished(array $session): bool
    {
        return in_array(self::of($session), [self::COMPLETED, self::FAILED, self::CANCELLED], true);
    }
}
