<?php

namespace Cabuz\Tembo\Tests;

use Cabuz\Tembo\Exceptions\AuthenticationException;
use Cabuz\Tembo\Exceptions\ConflictException;
use Cabuz\Tembo\Exceptions\ConnectionException;
use Cabuz\Tembo\Exceptions\InvalidRequestException;
use Cabuz\Tembo\Exceptions\MissingApiKeyException;
use Cabuz\Tembo\Exceptions\NotFoundException;
use Cabuz\Tembo\Exceptions\PermissionDeniedException;
use Cabuz\Tembo\Exceptions\RateLimitException;
use Cabuz\Tembo\Exceptions\ServerException;
use Cabuz\Tembo\Exceptions\TemboException;
use Cabuz\Tembo\Facades\Tembo;
use Cabuz\Tembo\Tembo as TemboClient;
use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

class ClientTest extends TestCase
{
    public function test_it_sends_authenticated_json_requests_to_the_api(): void
    {
        Http::fake(['api.tembo.io/v1/users/me' => Http::response(['id' => 'u1', 'email' => 'dev@example.com'])]);

        $this->assertSame(['id' => 'u1', 'email' => 'dev@example.com'], Tembo::me());

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tembo.io/v1/users/me'
            && $request->method() === 'GET'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request->hasHeader('Accept', 'application/json')
            && str_starts_with($request->header('User-Agent')[0], 'cabuz/laravel-tembo/'));
    }

    public function test_it_uses_the_configured_base_url(): void
    {
        config(['tembo.base_url' => 'https://tembo.test/']);
        $this->app->forgetInstance(TemboClient::class);
        Tembo::clearResolvedInstances();

        Http::fake(['tembo.test/*' => Http::response(['ok' => true])]);

        $this->assertSame(['ok' => true], Tembo::get('/v1/billing'));
        Http::assertSent(fn (Request $request) => $request->url() === 'https://tembo.test/v1/billing');
    }

    public function test_query_lists_are_comma_separated_and_booleans_are_words(): void
    {
        Http::fake(['*' => Http::response(['items' => [], 'nextCursor' => null])]);

        Tembo::sessions()->list([
            'repositoryIds' => ['a', 'b'],
            'includeTotal' => true,
            'excludePinned' => false,
            'search' => null,
            'projectIds' => [],
        ]);

        Http::assertSent(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query === ['repositoryIds' => 'a,b', 'includeTotal' => 'true', 'excludePinned' => 'false'];
        });
    }

    public function test_a_post_without_data_sends_no_body_and_empty_data_sends_an_empty_object(): void
    {
        Http::fake(['*' => Http::response([])]);

        Tembo::sessions()->stop('s1');
        Tembo::sessions()->fork('s1');

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/stop') && $request->body() === '');
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/fork') && $request->body() === '{}');
    }

    public function test_path_parameters_are_url_encoded(): void
    {
        Http::fake(['*' => Http::response([])]);

        Tembo::projects()->deleteEnvironmentVariable('p 1', 'A/B');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tembo.io/v1/projects/p%201/environment-variables/A%2FB');
    }

    public function test_an_empty_response_body_becomes_an_empty_array(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $this->assertSame([], Tembo::sessions()->delete('s1'));
    }

    /**
     * @return array<string, array{int, class-string<TemboException>}>
     */
    public static function errors(): array
    {
        return [
            '400' => [400, InvalidRequestException::class],
            '401' => [401, AuthenticationException::class],
            '403' => [403, PermissionDeniedException::class],
            '404' => [404, NotFoundException::class],
            '409' => [409, ConflictException::class],
            '422' => [422, InvalidRequestException::class],
            '429' => [429, RateLimitException::class],
            '500' => [500, ServerException::class],
            '502' => [502, ServerException::class],
            '418' => [418, TemboException::class],
        ];
    }

    /**
     * @param  class-string<TemboException>  $class
     */
    #[DataProvider('errors')]
    public function test_failed_responses_throw_the_matching_exception(int $status, string $class): void
    {
        config(['tembo.retries' => 0]);
        $this->app->forgetInstance(TemboClient::class);
        Tembo::clearResolvedInstances();

        Http::fake(['*' => Http::response(['error' => 'Something specific'], $status)]);

        try {
            Tembo::sessions()->get('s1');
            $this->fail('No exception thrown.');
        } catch (TemboException $e) {
            $this->assertSame($class, $e::class);
            $this->assertSame($status, $e->status());
            $this->assertSame($status, $e->getCode());
            $this->assertSame('Something specific', $e->error());
            $this->assertSame("Tembo API GET v1/sessions/s1 failed with {$status}: Something specific", $e->getMessage());
        }
    }

    public function test_the_api_key_never_appears_in_exception_messages(): void
    {
        Http::fake(['*' => Http::response(['error' => 'Unauthorized'], 401)]);

        try {
            Tembo::me();
        } catch (AuthenticationException $e) {
            $this->assertStringNotContainsString('test-key', $e->getMessage());
        }
    }

    public function test_get_requests_are_retried_on_server_errors(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['error' => 'Bad gateway'], 502)
            ->push(['id' => 's1'])]);

        $this->assertSame(['id' => 's1'], Tembo::sessions()->get('s1'));
        Http::assertSentCount(2);
    }

    public function test_writes_are_not_retried_on_server_errors(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['error' => 'Internal error'], 500)
            ->push(['id' => 's1'])]);

        $this->expectException(ServerException::class);

        try {
            Tembo::sessions()->create(['description' => 'Fix the bug']);
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function test_writes_are_retried_when_rate_limited(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['error' => 'Too many requests'], 429)
            ->push(['id' => 's1'])]);

        $this->assertSame(['id' => 's1'], Tembo::sessions()->create(['description' => 'Fix the bug']));
        Http::assertSentCount(2);
    }

    public function test_retries_give_up_after_the_configured_number_of_attempts(): void
    {
        Http::fake(['*' => Http::response(['error' => 'Slow down'], 429, ['Retry-After' => '7'])]);

        try {
            Tembo::sessions()->get('s1');
            $this->fail('No exception thrown.');
        } catch (RateLimitException $e) {
            $this->assertSame(7, $e->retryAfter());
        }

        Http::assertSentCount(3);
    }

    public function test_connection_errors_are_wrapped(): void
    {
        config(['tembo.retries' => 0]);
        $this->app->forgetInstance(TemboClient::class);
        Tembo::clearResolvedInstances();

        Http::fake(fn () => throw new HttpConnectionException('cURL error 6: Could not resolve host'));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Could not reach the Tembo API (GET v1/users/me)');

        Tembo::me();
    }

    public function test_a_missing_api_key_fails_with_a_clear_message(): void
    {
        config(['tembo.api_key' => null]);
        $this->app->forgetInstance(TemboClient::class);
        Tembo::clearResolvedInstances();

        $this->expectException(MissingApiKeyException::class);
        $this->expectExceptionMessage('TEMBO_API_KEY');

        Tembo::me();
    }

    public function test_with_api_key_returns_a_copy_that_uses_another_key(): void
    {
        Http::fake(['*' => Http::response([])]);

        Tembo::withApiKey('tenant-key')->me();
        Tembo::me();

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer tenant-key'));
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer test-key'));
        $this->assertSame('test-key', Tembo::config('api_key'));
    }

    public function test_the_client_is_a_singleton_with_a_string_alias(): void
    {
        $this->assertSame($this->app->make(TemboClient::class), $this->app->make('tembo'));
    }
}
