<?php

namespace Cabuz\Tembo\Exceptions;

/**
 * Too many requests (429), after all retries were used.
 */
class RateLimitException extends TemboException
{
    /**
     * Seconds to wait before retrying, from the Retry-After header.
     */
    public function retryAfter(): ?int
    {
        $header = $this->response?->header('Retry-After');

        return is_numeric($header) ? (int) $header : null;
    }
}
