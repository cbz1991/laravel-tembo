<?php

namespace Cabuz\Tembo\Tests;

use Cabuz\Tembo\Resources\Resource;
use Cabuz\Tembo\Tembo;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * Calls every public resource method and checks each request against the
 * endpoints in Tembo's OpenAPI schema (tests/Fixtures/endpoints.json,
 * refreshed with bin/update-endpoints).
 */
class EndpointCoverageTest extends TestCase
{
    /**
     * Endpoints deliberately not wrapped, with the reason.
     */
    private const NOT_WRAPPED = [
        'GET /oauth/context' => 'OAuth tokens only; API keys use authContext().',
        'POST /v1/live' => 'WebSocket subscriptions are out of scope for an HTTP client.',
        'GET /v1/live' => 'WebSocket subscriptions are out of scope for an HTTP client.',
        'PUT /v1/users/{userId}/profile-picture' => 'Binary upload; use Tembo::send() directly.',
    ];

    /**
     * Methods that poll or dispatch jobs instead of making one request.
     */
    private const SKIPPED_METHODS = ['wait', 'watch'];

    public function test_every_sdk_request_matches_a_documented_endpoint_and_every_endpoint_is_wrapped(): void
    {
        Http::fake(['*' => Http::response(['items' => [], 'nextCursor' => null, 'state' => ['current' => 'completed']])]);

        $tembo = $this->app->make(Tembo::class);
        $endpoints = $this->endpoints();
        $covered = [];
        $unknown = [];

        foreach ($this->resources($tembo) as $resource) {
            foreach ((new ReflectionClass($resource))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() === Resource::class
                    || $method->isConstructor()
                    || in_array($method->getName(), self::SKIPPED_METHODS, true)) {
                    continue;
                }

                $before = count(Http::recorded());
                $method->invokeArgs($resource, $this->arguments($method));
                $requests = array_slice(Http::recorded()->all(), $before);

                $this->assertNotEmpty($requests, $resource::class.'::'.$method->getName().' sent no request.');

                foreach ($requests as [$request]) {
                    /** @var Request $request */
                    $endpoint = $this->match($request, $endpoints);

                    if ($endpoint === null) {
                        $unknown[] = $resource::class.'::'.$method->getName().' -> '.$request->method().' '.parse_url($request->url(), PHP_URL_PATH);
                    } else {
                        $covered[$endpoint] = true;
                    }
                }
            }
        }

        // Shortcuts that live on the client itself.
        $before = count(Http::recorded());
        $tembo->authContext();
        foreach (array_slice(Http::recorded()->all(), $before) as [$request]) {
            $covered[$this->match($request, $endpoints) ?? 'unknown'] = true;
        }

        $this->assertSame([], $unknown, 'Requests to endpoints that are not in the OpenAPI schema.');

        $missing = array_values(array_diff(array_keys($endpoints), array_keys($covered), array_keys(self::NOT_WRAPPED)));
        $this->assertSame([], $missing, 'Endpoints in the OpenAPI schema without an SDK method.');
    }

    /**
     * @return list<resource>
     */
    private function resources(Tembo $tembo): array
    {
        $resources = [];

        foreach ((new ReflectionClass($tembo))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $type = $method->getReturnType();

            if ($type instanceof ReflectionNamedType && is_subclass_of($type->getName(), Resource::class)) {
                $resources[] = $method->invoke($tembo);
            }
        }

        $this->assertCount(18, $resources);

        return $resources;
    }

    /**
     * @return list<mixed>
     */
    private function arguments(ReflectionMethod $method): array
    {
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            if ($parameter->isOptional()) {
                break;
            }

            $type = $parameter->getType();
            $names = $type instanceof ReflectionUnionType
                ? array_map(fn ($t) => $t instanceof ReflectionNamedType ? $t->getName() : 'mixed', $type->getTypes())
                : [$type instanceof ReflectionNamedType ? $type->getName() : 'mixed'];

            $arguments[] = match (true) {
                in_array('string', $names, true) => 'id1',
                in_array('int', $names, true) => 1,
                in_array('array', $names, true) => [],
                default => null,
            };
        }

        return $arguments;
    }

    /**
     * @return array<string, string> "METHOD /path/{param}" => regex
     */
    private function endpoints(): array
    {
        $list = json_decode((string) file_get_contents(__DIR__.'/Fixtures/endpoints.json'), true);
        $endpoints = [];

        // Literal paths first, so /v1/sessions/sources wins over /v1/sessions/{sessionId}.
        usort($list, fn ($a, $b) => substr_count($a, '{') <=> substr_count($b, '{'));

        foreach ($list as $endpoint) {
            [$verb, $path] = explode(' ', $endpoint, 2);
            $endpoints[$endpoint] = '#^'.$verb.' '.preg_replace('#\\\\\{[^/]+\\\\\}#', '[^/]+', preg_quote($path, '#')).'$#';
        }

        return $endpoints;
    }

    /**
     * @param  array<string, string>  $endpoints
     */
    private function match(Request $request, array $endpoints): ?string
    {
        $actual = $request->method().' '.parse_url($request->url(), PHP_URL_PATH);

        foreach ($endpoints as $endpoint => $pattern) {
            if (preg_match($pattern, $actual)) {
                return $endpoint;
            }
        }

        return null;
    }
}
