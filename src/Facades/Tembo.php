<?php

namespace Cabuz\Tembo\Facades;

use Cabuz\Tembo\Tembo as TemboClient;
use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;

/**
 * @method static TemboClient withApiKey(string $apiKey)
 * @method static TemboClient withConfig(array<string, mixed> $config)
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

        $stubs[$base.'/*'] = Http::response([]);

        Http::fake($stubs);
    }

    protected static function getFacadeAccessor(): string
    {
        return TemboClient::class;
    }
}
