<?php

namespace Cabuz\Tembo\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

/**
 * Base exception for every error thrown by the package.
 *
 * Catch this class to handle any Tembo failure, or one of the subclasses to
 * handle a specific HTTP status.
 */
class TemboException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?Response $response = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Build the matching exception for a failed API response.
     */
    public static function fromResponse(Response $response, string $method, string $path): self
    {
        $status = $response->status();
        $error = $response->json('error');
        $error = is_string($error) && $error !== '' ? $error : ($response->reason() ?: 'Unknown error');

        $message = sprintf('Tembo API %s %s failed with %d: %s', $method, $path, $status, $error);

        $class = match (true) {
            $status === 400, $status === 413, $status === 422 => InvalidRequestException::class,
            $status === 401 => AuthenticationException::class,
            $status === 403 => PermissionDeniedException::class,
            $status === 404 => NotFoundException::class,
            $status === 409 => ConflictException::class,
            $status === 429 => RateLimitException::class,
            $status >= 500 => ServerException::class,
            default => self::class,
        };

        return new $class($message, $status, null, $response);
    }

    /**
     * The "error" message returned by Tembo, if any.
     */
    public function error(): ?string
    {
        $error = $this->response?->json('error');

        return is_string($error) ? $error : null;
    }

    public function status(): ?int
    {
        return $this->response?->status();
    }
}
