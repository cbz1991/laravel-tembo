<?php

namespace Cabuz\Tembo\Resources;

use Cabuz\Tembo\Exceptions\LiveSubscriptionException;
use Cabuz\Tembo\Exceptions\MissingWebSocketClientException;
use Cabuz\Tembo\Exceptions\TemboException;
use Cabuz\Tembo\Live\LiveConnection;
use Cabuz\Tembo\Live\PhritySocketFactory;
use Cabuz\Tembo\Live\SocketFactory;
use Generator;
use Illuminate\Container\Container;
use Illuminate\Support\Sleep;

/**
 * Tembo's live API: a WebSocket that streams a session's output as the agent
 * produces it. Needs the optional phrity/websocket package.
 */
class Live extends Resource
{
    /**
     * A single-use ticket for connect(). Tickets expire after 30 seconds.
     *
     * @return array<string, mixed>
     */
    public function authorize(): array
    {
        return $this->tembo->post('v1/live');
    }

    /**
     * Open a raw live connection with a ticket from authorize().
     *
     * @param  float  $timeout  Seconds to wait for each frame.
     */
    public function connect(string $ticket, float $timeout = 15): LiveConnection
    {
        $url = rtrim((string) $this->tembo->config('base_url', 'https://api.tembo.io'), '/').'/v1/live?ticket='.rawurlencode($ticket);
        $url = (string) preg_replace('#^http(s?)://#', 'ws$1://', $url);

        return new LiveConnection($this->socketFactory()->connect($url, $timeout));
    }

    /**
     * Subscribe to a live resource and yield its frames:
     * ['type' => 'ready'] once the subscription is authorized, then
     * ['type' => 'delta', 'data' => [...]] for each output event. When
     * nothing arrives for $options['timeout'] seconds it yields
     * ['type' => 'idle'], so the loop can check other conditions.
     *
     * Every reconnect gets a fresh ticket and subscribes again. This is a live
     * stream, not a replay: reconcile missed output with listEvents().
     * Stop by breaking out of the loop.
     *
     * @param  array<string, mixed>  $params  ['resource' => 'sessions.events', 'input' => ['scope' => ['sessionId' => ...]]]
     * @param  array{maxRetries?: int, initialDelay?: int, maxDelay?: int, timeout?: int|float}  $options
     *                                                                                                     Delays in milliseconds, timeout in seconds.
     * @return Generator<int, array<string, mixed>>
     *
     * @throws LiveSubscriptionException when Tembo rejects the subscription.
     * @throws TemboException when the retries run out.
     */
    public function subscribe(array $params, array $options = []): Generator
    {
        $maxRetries = $options['maxRetries'] ?? 5;
        $initialDelay = $options['initialDelay'] ?? 500;
        $maxDelay = $options['maxDelay'] ?? 8000;
        $timeout = (float) ($options['timeout'] ?? 15);
        $failures = 0;

        while (true) {
            $connection = null;

            try {
                $ticket = $this->authorize()['ticket'] ?? null;

                if (! is_string($ticket) || $ticket === '') {
                    throw new LiveSubscriptionException('Tembo did not return a live ticket.', 500);
                }

                $connection = $this->connect($ticket, $timeout);
                $deadline = microtime(true) + $timeout;
                $subscribed = false;
                $ready = false;

                while (true) {
                    $frame = $connection->receive();

                    if ($frame === null) {
                        if (! $ready && microtime(true) >= $deadline) {
                            throw new LiveSubscriptionException('The live subscription timed out.', 504);
                        }

                        if ($ready) {
                            yield ['type' => 'idle'];
                        }

                        continue;
                    }

                    $id = $frame['id'] ?? null;

                    if ($frame['type'] === 'ready' && $id === null) {
                        if ($subscribed) {
                            throw new LiveSubscriptionException('Repeated connection readiness.', 400);
                        }

                        $subscribed = true;
                        $connection->send(['op' => 'subscribe', 'id' => 'events'] + $params);

                        continue;
                    }

                    if ($id !== 'events') {
                        continue;
                    }

                    if ($frame['type'] === 'error') {
                        throw new LiveSubscriptionException(
                            is_string($frame['message'] ?? null) ? $frame['message'] : 'Live subscription failed.',
                            is_int($frame['status'] ?? null) ? $frame['status'] : 400,
                        );
                    }

                    if ($frame['type'] === 'ready') {
                        $ready = true;
                        $failures = 0;

                        yield ['type' => 'ready'];
                    } elseif ($frame['type'] === 'event') {
                        if (! $ready) {
                            throw new LiveSubscriptionException('Live event before subscription readiness.', 400);
                        }

                        $data = $frame['data'] ?? null;

                        if (is_array($data)) {
                            yield $data;
                        }
                    }
                }
            } catch (MissingWebSocketClientException $e) {
                throw $e;
            } catch (TemboException $e) {
                $status = $e->status() ?? $e->getCode();

                if (($status >= 400 && $status < 500 && $status !== 429) || ++$failures > $maxRetries) {
                    throw $e;
                }
            } finally {
                $connection?->close();
            }

            Sleep::for(min($initialDelay * 2 ** ($failures - 1), $maxDelay))->milliseconds();
        }
    }

    protected function socketFactory(): SocketFactory
    {
        $container = Container::getInstance();

        return $container->bound(SocketFactory::class)
            ? $container->make(SocketFactory::class)
            : new PhritySocketFactory;
    }
}
