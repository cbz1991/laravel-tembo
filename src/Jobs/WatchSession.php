<?php

namespace Cabuz\Tembo\Jobs;

use Cabuz\Tembo\Events\SessionCancelled;
use Cabuz\Tembo\Events\SessionCompleted;
use Cabuz\Tembo\Events\SessionFailed;
use Cabuz\Tembo\Events\SessionWatchTimedOut;
use Cabuz\Tembo\Support\SessionState;
use Cabuz\Tembo\Tembo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Polls one session and re-dispatches itself with a delay until the session
 * finishes or the deadline passes. Each poll is a short job, so no worker is
 * blocked while the agent runs.
 *
 * The payload is encrypted because it may carry a per-tenant API key.
 */
class WatchSession implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Attempts per poll, for transient API errors.
     */
    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $sessionId,
        public array $context = [],
        public int $interval = 30,
        public int $deadline = 0,
        public ?string $apiKey = null,
    ) {
        $this->onConnection(config('tembo.watch.connection'));
        $this->onQueue(config('tembo.watch.queue'));
    }

    public function handle(Tembo $tembo, Dispatcher $events): void
    {
        if ($this->apiKey !== null) {
            $tembo = $tembo->withApiKey($this->apiKey);
        }

        $session = $tembo->sessions()->retrieve($this->sessionId);

        $event = match (SessionState::of($session)) {
            SessionState::COMPLETED => new SessionCompleted($this->sessionId, $session, $this->context),
            SessionState::FAILED => new SessionFailed($this->sessionId, $session, $this->context),
            SessionState::CANCELLED => new SessionCancelled($this->sessionId, $session, $this->context),
            default => null,
        };

        if ($event === null && time() >= $this->deadline) {
            $event = new SessionWatchTimedOut($this->sessionId, $session, $this->context);
        }

        if ($event !== null) {
            $events->dispatch($event);

            return;
        }

        $next = new self($this->sessionId, $this->context, $this->interval, $this->deadline, $this->apiKey);
        $next->onConnection($this->connection)->onQueue($this->queue)->delay($this->interval);

        dispatch($next);
    }
}
