<?php

namespace Cabuz\Tembo\Tests;

use Cabuz\Tembo\Live\SocketFactory;
use Cabuz\Tembo\Tests\Fakes\FakeSocket;
use Cabuz\Tembo\Tests\Fakes\FakeSocketFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

class CommandsTest extends TestCase
{
    public function test_check_reports_the_connected_user(): void
    {
        Http::fake(['*/v1/users/me' => Http::response(['name' => 'Florin', 'email' => 'dev@example.com'])]);

        $this->artisan('tembo:check')
            ->expectsOutputToContain('Connected to Tembo as Florin <dev@example.com>.')
            ->assertSuccessful();
    }

    public function test_check_fails_with_the_api_error(): void
    {
        Http::fake(['*' => Http::response(['error' => 'Invalid API key'], 401)]);

        $this->artisan('tembo:check')
            ->expectsOutputToContain('Invalid API key')
            ->assertFailed();
    }

    public function test_run_resolves_repository_names_and_starts_a_session(): void
    {
        $uuid = '22222222-2222-4222-8222-222222222222';

        Http::fake([
            '*/v1/repositories*' => Http::response(['items' => [['id' => 'repo-1', 'owner' => 'acme', 'name' => 'api']], 'nextCursor' => null]),
            '*/v1/sessions' => Http::response($this->fakeSession('queued', ['htmlUrl' => 'https://app.tembo.io/sessions/s1']), 201),
        ]);

        $this->artisan('tembo:run', ['task' => 'Fix the login bug', '--repo' => ['acme/api', $uuid], '--agent' => 'codex'])
            ->expectsOutputToContain('Session started: 11111111-1111-4111-8111-111111111111')
            ->expectsOutputToContain('https://app.tembo.io/sessions/s1')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tembo.io/v1/sessions'
            && $request->data() === [
                'description' => 'Fix the login bug',
                'codeRepositoryIds' => ['repo-1', $uuid],
                'agent' => 'codex',
                'queueRightAway' => true,
            ]);
    }

    public function test_run_fails_for_an_unknown_repository(): void
    {
        Http::fake(['*' => Http::response(['items' => [], 'nextCursor' => null])]);

        $this->artisan('tembo:run', ['task' => 'Fix it', '--repo' => ['acme/missing']])
            ->expectsOutputToContain('Repository [acme/missing] was not found in Tembo.')
            ->assertFailed();

        Http::assertNotSent(fn (Request $request) => $request->method() === 'POST');
    }

    public function test_run_can_wait_for_the_result(): void
    {
        Sleep::fake();
        Http::fake([
            '*/v1/sessions' => Http::response($this->fakeSession('queued'), 201),
            '*/v1/sessions/*' => Http::sequence()
                ->push($this->fakeSession('inProgress'))
                ->push($this->fakeSession('failed')),
        ]);

        $this->artisan('tembo:run', ['task' => 'Fix it', '--wait' => true])
            ->expectsOutputToContain('failed')
            ->assertFailed();
    }

    public function test_run_can_follow_the_live_output(): void
    {
        $this->fakeFollow(['type' => 'delta', 'data' => ['event' => 'response.output_text.delta', 'provider' => 'openai_responses', 'message' => '{"delta":"Reading the tests"}']]);

        $this->artisan('tembo:run', ['task' => 'Fix it', '--follow' => true])
            ->expectsOutputToContain('Reading the tests')
            ->doesntExpectOutputToContain('response.output_text.delta')
            ->expectsOutputToContain('completed')
            ->assertSuccessful();
    }

    public function test_run_can_follow_the_raw_events(): void
    {
        $this->fakeFollow(['type' => 'delta', 'data' => ['event' => 'response.output_text.delta', 'provider' => 'openai_responses', 'message' => '{"delta":"Reading the tests"}']]);

        $this->artisan('tembo:run', ['task' => 'Fix it', '--follow' => true, '--raw' => true])
            ->expectsOutputToContain('{"delta":"Reading the tests"}')
            ->assertSuccessful();
    }

    /**
     * @param  array<string, mixed>  $delta
     */
    private function fakeFollow(array $delta): void
    {
        Http::fake([
            '*/v1/sessions' => Http::response($this->fakeSession('queued'), 201),
            '*/v1/live' => Http::response(['ticket' => 't1']),
            '*/v1/sessions/*' => Http::response($this->fakeSession('completed')),
        ]);
        $this->app->instance(SocketFactory::class, new FakeSocketFactory([new FakeSocket([
            ['type' => 'ready'],
            ['type' => 'ready', 'id' => 'events'],
            ['type' => 'event', 'id' => 'events', 'data' => $delta],
            null,
        ])]));
    }
}
