<?php

namespace Cabuz\Tembo\Facades;

use Cabuz\Tembo\Live\SocketFactory;
use Cabuz\Tembo\Tembo as TemboClient;
use Cabuz\Tembo\Testing\FakeLive;
use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;

/**
 * @method static TemboClient withApiKey(string $apiKey)
 * @method static TemboClient withHeaders(array<string, string> $headers)
 * @method static TemboClient withQuery(array<string, mixed> $query)
 * @method static TemboClient withTimeout(int $seconds)
 * @method static TemboClient withRetries(int $retries)
 * @method static TemboClient withConfig(array<string, mixed> $config)
 * @method static \Illuminate\Http\Client\Response|null lastResponse()
 * @method static mixed config(string $key, mixed $default = null)
 * @method static \Cabuz\Tembo\Resources\Sessions sessions()
 * @method static \Cabuz\Tembo\Resources\Messages messages()
 * @method static \Cabuz\Tembo\Resources\Agents agents()
 * @method static \Cabuz\Tembo\Resources\Repositories repositories()
 * @method static \Cabuz\Tembo\Resources\Projects projects()
 * @method static \Cabuz\Tembo\Resources\PullRequests pullRequests()
 * @method static \Cabuz\Tembo\Resources\Artifacts artifacts()
 * @method static \Cabuz\Tembo\Resources\Models models()
 * @method static \Cabuz\Tembo\Resources\Runtimes runtimes()
 * @method static \Cabuz\Tembo\Resources\Skills skills()
 * @method static \Cabuz\Tembo\Resources\McpConnections mcpConnections()
 * @method static \Cabuz\Tembo\Resources\Integrations integrations()
 * @method static \Cabuz\Tembo\Resources\Users users()
 * @method static \Cabuz\Tembo\Resources\ApiKeys apiKeys()
 * @method static \Cabuz\Tembo\Resources\Organizations organizations()
 * @method static \Cabuz\Tembo\Resources\Billing billing()
 * @method static \Cabuz\Tembo\Resources\Insights insights()
 * @method static \Cabuz\Tembo\Resources\Pins pins()
 * @method static \Cabuz\Tembo\Resources\Live live()
 * @method static array<string, mixed> me()
 * @method static array<string, mixed> authContext()
 * @method static array<string, mixed> get(string $path, array<string, mixed> $query = [])
 * @method static array<string, mixed> post(string $path, array<string, mixed>|null $data = null, array<string, mixed> $query = [])
 * @method static array<string, mixed> put(string $path, array<string, mixed>|null $data = null, array<string, mixed> $query = [])
 * @method static array<string, mixed> patch(string $path, array<string, mixed>|null $data = null, array<string, mixed> $query = [])
 * @method static array<string, mixed> delete(string $path, array<string, mixed> $query = [], array<string, mixed>|null $data = null)
 * @method static \Cabuz\Tembo\Support\CursorPage paginate(string $path, array<string, mixed> $query = [], string $itemsKey = 'items')
 * @method static array<string, mixed> request(string $method, string $path, array<string, mixed> $query = [], array<string, mixed>|null $data = null)
 * @method static \Illuminate\Http\Client\Response send(string $method, string $path, array<string, mixed> $query = [], array<string, mixed>|null $data = null)
 * @method static array<string, mixed> upload(string $method, string $path, array<int, array<string, mixed>> $parts)
 *
 * @see TemboClient
 */
class Tembo extends Facade
{
    /**
     * Fake the Tembo API for tests.
     *
     * Keys are paths relative to the API (wildcards allowed, e.g.
     * "v1/sessions/*"), values are JSON arrays or Http::response() /
     * Http::sequence() stubs. Unmatched Tembo requests get an empty 200 so
     * tests never reach the real API. Assert on requests with Http::assertSent().
     *
     * @param  array<string, array<mixed>|PromiseInterface|Closure>  $responses
     */
    public static function fake(array $responses = []): void
    {
        $app = static::getFacadeApplication();

        if ($app !== null && blank($app['config']->get('tembo.api_key'))) {
            $app['config']->set('tembo.api_key', 'tembo-fake-key');
            $app->forgetInstance(TemboClient::class);
            static::clearResolvedInstance(TemboClient::class);
        }

        $base = rtrim((string) ($app?->make('config')->get('tembo.base_url') ?? 'https://api.tembo.io'), '/');
        $stubs = [];

        foreach ($responses as $path => $response) {
            $url = str_starts_with($path, 'http') ? $path : $base.'/'.ltrim($path, '/');
            $stubs[$url] = is_array($response) ? Http::response($response) : $response;
        }

        $stubs[$base.'/v1/live'] ??= Http::response(['ticket' => 'fake-ticket']);
        $stubs[$base.'/*'] = Http::response([]);

        Http::fake($stubs);
    }

    /**
     * Fake Tembo's live API for tests. subscribe() yields "ready", then a
     * "delta" frame for each output event given here, then "idle" frames.
     *
     * Each event is the delta's data, e.g. ['event' => 'output', 'message' => '...'],
     * or a full ['type' => 'delta', 'data' => [...]] frame.
     *
     * @param  list<array<string, mixed>>  $events
     */
    public static function fakeLive(array $events = []): FakeLive
    {
        $deltas = array_map(
            fn (array $event) => ($event['type'] ?? null) === 'delta' ? $event : ['type' => 'delta', 'data' => $event],
            $events,
        );

        $fake = new FakeLive($deltas);
        $app = static::getFacadeApplication();
        $app?->instance(SocketFactory::class, $fake);

        $base = rtrim((string) ($app?->make('config')->get('tembo.base_url') ?? 'https://api.tembo.io'), '/');
        Http::fake([$base.'/v1/live' => Http::response(['ticket' => 'fake-ticket'])]);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return TemboClient::class;
    }
}
