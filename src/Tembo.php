<?php

namespace Cabuz\Tembo;

use Cabuz\Tembo\Exceptions\ConnectionException;
use Cabuz\Tembo\Exceptions\MissingApiKeyException;
use Cabuz\Tembo\Exceptions\TemboException;
use Cabuz\Tembo\Resources\Agents;
use Cabuz\Tembo\Resources\ApiKeys;
use Cabuz\Tembo\Resources\Artifacts;
use Cabuz\Tembo\Resources\Billing;
use Cabuz\Tembo\Resources\Insights;
use Cabuz\Tembo\Resources\Integrations;
use Cabuz\Tembo\Resources\McpConnections;
use Cabuz\Tembo\Resources\Messages;
use Cabuz\Tembo\Resources\Models;
use Cabuz\Tembo\Resources\Organizations;
use Cabuz\Tembo\Resources\Pins;
use Cabuz\Tembo\Resources\Projects;
use Cabuz\Tembo\Resources\PullRequests;
use Cabuz\Tembo\Resources\Repositories;
use Cabuz\Tembo\Resources\Runtimes;
use Cabuz\Tembo\Resources\Sessions;
use Cabuz\Tembo\Resources\Skills;
use Cabuz\Tembo\Resources\Users;
use Cabuz\Tembo\Support\CursorPage;
use Composer\InstalledVersions;
use DateTimeInterface;
use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use stdClass;
use Throwable;

/**
 * Entry point of the SDK: one accessor per Tembo API resource, plus raw
 * get/post/put/patch/delete methods for anything not wrapped yet.
 */
class Tembo
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected HttpFactory $http,
        protected array $config = [],
    ) {}

    /**
     * A copy of the client that uses another API key (for example per tenant).
     */
    public function withApiKey(string $apiKey): static
    {
        return $this->withConfig(['api_key' => $apiKey]);
    }

    /**
     * A copy of the client with some config values replaced.
     *
     * @param  array<string, mixed>  $config
     */
    public function withConfig(array $config): static
    {
        $clone = clone $this;
        $clone->config = array_replace($this->config, $config);

        return $clone;
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    // ------------------------------------------------------------------
    // Resources
    // ------------------------------------------------------------------

    public function sessions(): Sessions
    {
        return new Sessions($this);
    }

    public function messages(): Messages
    {
        return new Messages($this);
    }

    public function agents(): Agents
    {
        return new Agents($this);
    }

    public function repositories(): Repositories
    {
        return new Repositories($this);
    }

    public function projects(): Projects
    {
        return new Projects($this);
    }

    public function pullRequests(): PullRequests
    {
        return new PullRequests($this);
    }

    public function artifacts(): Artifacts
    {
        return new Artifacts($this);
    }

    public function models(): Models
    {
        return new Models($this);
    }

    public function runtimes(): Runtimes
    {
        return new Runtimes($this);
    }

    public function skills(): Skills
    {
        return new Skills($this);
    }

    public function mcpConnections(): McpConnections
    {
        return new McpConnections($this);
    }

    public function integrations(): Integrations
    {
        return new Integrations($this);
    }

    public function users(): Users
    {
        return new Users($this);
    }

    public function apiKeys(): ApiKeys
    {
        return new ApiKeys($this);
    }

    public function organizations(): Organizations
    {
        return new Organizations($this);
    }

    public function billing(): Billing
    {
        return new Billing($this);
    }

    public function insights(): Insights
    {
        return new Insights($this);
    }

    public function pins(): Pins
    {
        return new Pins($this);
    }

    /**
     * The user who owns the API key.
     *
     * @return array<string, mixed>
     */
    public function me(): array
    {
        return $this->users()->me();
    }

    /**
     * The authenticated principal (user, organization and key details).
     *
     * @return array<string, mixed>
     */
    public function authContext(): array
    {
        return $this->get('auth/context');
    }

    // ------------------------------------------------------------------
    // Raw requests
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query);
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function post(string $path, ?array $data = null, array $query = []): array
    {
        return $this->request('POST', $path, $query, $data);
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function put(string $path, ?array $data = null, array $query = []): array
    {
        return $this->request('PUT', $path, $query, $data);
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function patch(string $path, ?array $data = null, array $query = []): array
    {
        return $this->request('PATCH', $path, $query, $data);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function delete(string $path, array $query = []): array
    {
        return $this->request('DELETE', $path, $query);
    }

    /**
     * Fetch the first page of a cursor-paginated list.
     *
     * @param  array<string, mixed>  $query
     */
    public function paginate(string $path, array $query = [], string $itemsKey = 'items'): CursorPage
    {
        return new CursorPage($this, $path, $query, $this->get($path, $query), $itemsKey);
    }

    /**
     * Send a request and return the decoded JSON body.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $data  null sends no body; [] sends {}.
     * @return array<string, mixed>
     *
     * @throws TemboException
     */
    public function request(string $method, string $path, array $query = [], ?array $data = null): array
    {
        $response = $this->send($method, $path, $query, $data);

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * Send a request and return the raw response (for binary endpoints such
     * as profile pictures). Failed responses still throw.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $data
     *
     * @throws TemboException
     */
    public function send(string $method, string $path, array $query = [], ?array $data = null): Response
    {
        $method = strtoupper($method);
        $path = ltrim($path, '/');

        $options = [];

        if ($query !== []) {
            $options['query'] = static::normalizeQuery($query);
        }

        if ($data !== null) {
            $options['json'] = $data === [] ? new stdClass : $data;
        }

        try {
            $response = $this->pendingRequest($method)->send($method, $path, $options);
        } catch (HttpConnectionException $e) {
            throw new ConnectionException(sprintf('Could not reach the Tembo API (%s %s): %s', $method, $path, $e->getMessage()), 0, $e);
        }

        if ($response->failed()) {
            throw TemboException::fromResponse($response, $method, $path);
        }

        return $response;
    }

    /**
     * The configured HTTP request, before the method and URL are applied.
     */
    public function pendingRequest(string $method = 'GET'): PendingRequest
    {
        $apiKey = $this->config('api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw MissingApiKeyException::make();
        }

        $request = $this->http
            ->baseUrl(rtrim((string) $this->config('base_url', 'https://api.tembo.io'), '/'))
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->withUserAgent('cabuz/laravel-tembo/'.static::version())
            ->timeout((int) $this->config('timeout', 30));

        $retries = max(0, (int) $this->config('retries', 2));

        if ($retries === 0) {
            return $request;
        }

        $delay = max(0, (int) $this->config('retry_delay', 500));
        $idempotent = in_array($method, ['GET', 'HEAD'], true);

        return $request->retry(
            $retries + 1,
            $delay === 0 ? 0 : static function (int $attempt, ?Throwable $e = null) use ($delay): int {
                $retryAfter = $e instanceof RequestException ? $e->response->header('Retry-After') : '';

                if (is_numeric($retryAfter)) {
                    return min((int) $retryAfter, 60) * 1000;
                }

                return $delay * (2 ** ($attempt - 1));
            },
            static function (Throwable $e) use ($idempotent): bool {
                if ($e instanceof HttpConnectionException) {
                    return $idempotent;
                }

                if ($e instanceof RequestException) {
                    $status = $e->response->status();

                    return $status === 429 || ($idempotent && $status >= 500);
                }

                return false;
            },
            throw: false,
        );
    }

    /**
     * The installed package version, for the User-Agent header.
     */
    public static function version(): string
    {
        try {
            return InstalledVersions::getPrettyVersion('cabuz/laravel-tembo') ?? 'dev';
        } catch (Throwable) {
            return 'dev';
        }
    }

    /**
     * Tembo expects list filters as comma-separated values and booleans as
     * "true"/"false"; null values and empty lists are dropped.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, scalar>
     */
    public static function normalizeQuery(array $query): array
    {
        $normalized = [];

        foreach ($query as $key => $value) {
            if ($value === null || $value === []) {
                continue;
            }

            if (is_array($value)) {
                $value = implode(',', array_map(static fn ($item) => static::scalar($item), $value));
            } else {
                $value = static::scalar($value);
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    protected static function scalar(mixed $value): string|int|float
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            $value instanceof \BackedEnum => $value->value,
            is_int($value), is_float($value) => $value,
            default => (string) $value,
        };
    }
}
