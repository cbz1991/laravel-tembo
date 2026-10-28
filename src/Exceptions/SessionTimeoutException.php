<?php

namespace Cabuz\Tembo\Exceptions;

/**
 * Thrown by Sessions::wait() when the session doesn't finish in time.
 */
class SessionTimeoutException extends TemboException
{
    /**
     * The last session state seen before giving up.
     *
     * @var array<string, mixed>
     */
    public array $session = [];

    /**
     * @param  array<string, mixed>  $session
     */
    public static function make(string $sessionId, int $timeout, array $session = []): self
    {
        $exception = new self(sprintf('Tembo session %s did not finish within %d seconds.', $sessionId, $timeout));
        $exception->session = $session;

        return $exception;
    }
}
