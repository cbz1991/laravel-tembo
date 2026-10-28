<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Exceptions\SessionTimeoutException;
use Cabuz\Tembo\Jobs\WatchSession;
use Cabuz\Tembo\Support\CursorPage;
use Cabuz\Tembo\Support\SessionState;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Sleep;

/**
 * Sessions are coding-agent workspaces: create one with a task and Tembo
 * starts an agent on your repositories, usually ending in a pull request.
 */
class Sessions extends Resource
{
    /**
     * @param  array<string, mixed>  $query  state, search, repositoryIds, projectIds, createdAfter, includeTotal, limit, cursor...
     */
    public function list(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/sessions', $query);
    }

    /**
     * Create a session.
     *
     * @param  array<string, mixed>  $data  description (the task) is required; also agent,
     *                                      codeRepositoryIds, projectId, baseBranch, targetBranch,
     *                                      queueRightAway, sandboxSize, visibility, mcpServers...
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->tembo->post('v1/sessions', $data);
    }

    /**
     * Create a session and queue its task right away.
     *
     * @param  array<string, mixed>  $options  Any create() field.
     * @return array<string, mixed>
     */
    public function start(string $task, array $options = []): array
    {
        return $this->create(['description' => $task] + $options + ['queueRightAway' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $sessionId): array
    {
        return $this->tembo->get($this->path('v1/sessions/%s', $sessionId));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $sessionId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/sessions/%s', $sessionId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $sessionId): array
    {
        return $this->tembo->delete($this->path('v1/sessions/%s', $sessionId));
    }

    /**
     * @param  array<string, mixed>  $data  prompt, title, queueRightAway
     * @return array<string, mixed>
     */
    public function fork(string $sessionId, array $data = []): array
    {
        return $this->tembo->post($this->path('v1/sessions/%s/fork', $sessionId), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function stop(string $sessionId): array
    {
        return $this->tembo->post($this->path('v1/sessions/%s/stop', $sessionId));
    }

    /**
     * Send a follow-up task (or, with deliveryMode "steer", guidance for the
     * running turn) to an existing session.
     *
     * @param  array<string, mixed>  $options  deliveryMode, agentOptions, mcpServers...
     * @return array<string, mixed>
     */
    public function sendMessage(string $sessionId, string $content, array $options = []): array
    {
        return $this->tembo->messages()->create(['sessionId' => $sessionId, 'content' => $content] + $options);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function messages(string $sessionId, array $query = []): CursorPage
    {
        return $this->tembo->messages()->list(['sessionId' => $sessionId] + $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function inputs(string $sessionId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/sessions/%s/inputs', $sessionId), $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateInput(string $sessionId, string $inputId, array $data): array
    {
        return $this->tembo->patch($this->path('v1/sessions/%s/inputs/%s', $sessionId, $inputId), $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateQueue(string $sessionId, array $data): array
    {
        return $this->tembo->put($this->path('v1/sessions/%s/queue', $sessionId), $data);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function commands(string $sessionId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/sessions/%s/commands', $sessionId), $query);
    }

    /**
     * @param  array<string, mixed>  $query  messageId, after, limit, cursor
     */
    public function events(string $sessionId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/sessions/%s/events', $sessionId), $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function diffs(string $sessionId, array $query = []): CursorPage
    {
        return $this->tembo->paginate($this->path('v1/sessions/%s/diffs', $sessionId), $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function diff(string $sessionId, string $diffId): array
    {
        return $this->tembo->get($this->path('v1/sessions/%s/diffs/%s', $sessionId, $diffId));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function sources(array $query = []): CursorPage
    {
        return $this->tembo->paginate('v1/sessions/sources', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function source(string $sourceId): array
    {
        return $this->tembo->get($this->path('v1/sessions/sources/%s', $sourceId));
    }

    /**
     * The current state: queued, inProgress, completed, failed or cancelled.
     */
    public function state(string $sessionId): ?string
    {
        return SessionState::of($this->get($sessionId));
    }

    /**
     * Poll the session until it completes, fails or is cancelled, and return
     * its final data. This blocks the current process; in web requests use
     * watch() instead.
     *
     * @return array<string, mixed>
     *
     * @throws SessionTimeoutException
     */
    public function wait(string $sessionId, ?int $timeout = null, ?int $interval = null): array
    {
        $watch = (array) $this->tembo->config('watch', []);
        $timeout ??= (int) ($watch['timeout'] ?? 3600);
        $interval = max(1, $interval ?? (int) ($watch['interval'] ?? 30));
        $elapsed = 0;

        while (true) {
            $session = $this->get($sessionId);

            if (SessionState::isFinished($session)) {
                return $session;
            }

            if ($elapsed >= $timeout) {
                throw SessionTimeoutException::make($sessionId, $timeout, $session);
            }

            Sleep::for($interval)->seconds();
            $elapsed += $interval;
        }
    }

    /**
     * Watch the session from a queued job and fire SessionCompleted,
     * SessionFailed, SessionCancelled or SessionWatchTimedOut when it ends.
     *
     * @param  array<string, mixed>  $context  Anything you need back in the event listener (e.g. a model ID).
     */
    public function watch(string $sessionId, array $context = [], ?int $timeout = null, ?int $interval = null): PendingDispatch
    {
        $watch = (array) $this->tembo->config('watch', []);
        $timeout ??= (int) ($watch['timeout'] ?? 3600);
        $interval = max(1, $interval ?? (int) ($watch['interval'] ?? 30));

        $apiKey = $this->tembo->config('api_key');

        return WatchSession::dispatch(
            $sessionId,
            $context,
            $interval,
            time() + $timeout,
            $apiKey !== config('tembo.api_key') && is_string($apiKey) ? $apiKey : null,
        );
    }
}
