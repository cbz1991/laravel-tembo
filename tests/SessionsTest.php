<?php

namespace Cabuz\Tembo\Tests;

use Cabuz\Tembo\Exceptions\SessionTimeoutException;
use Cabuz\Tembo\Facades\Tembo;
use Cabuz\Tembo\Support\SessionState;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

class SessionsTest extends TestCase
{
    public function test_start_creates_a_session_and_queues_the_task(): void
    {
        Http::fake(['*' => Http::response($this->fakeSession('queued'), 201)]);

        $session = Tembo::sessions()->start('Fix the login bug', [
            'codeRepositoryIds' => ['repo-1'],
            'agent' => 'claudeCode:claude-fable-5-1',
        ]);

        $this->assertSame('queued', SessionState::of($session));
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'https://api.tembo.io/v1/sessions'
            && $request->data() === [
                'description' => 'Fix the login bug',
                'codeRepositoryIds' => ['repo-1'],
                'agent' => 'claudeCode:claude-fable-5-1',
                'queueRightAway' => true,
            ]);
    }

    public function test_start_options_can_override_queueing(): void
    {
        Http::fake(['*' => Http::response($this->fakeSession('queued'))]);

        Tembo::sessions()->start('Plan only', ['queueRightAway' => false]);

        Http::assertSent(fn (Request $request) => $request['queueRightAway'] === false);
    }

    public function test_send_message_posts_a_follow_up_to_the_session(): void
    {
        Http::fake(['*' => Http::response(['id' => 'm1'])]);

        Tembo::sessions()->sendMessage('s1', 'Also add a test', ['deliveryMode' => 'steer']);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tembo.io/v1/messages'
            && $request->data() === ['sessionId' => 's1', 'content' => 'Also add a test', 'deliveryMode' => 'steer']);
    }

    public function test_messages_lists_the_session_messages(): void
    {
        Http::fake(['*' => Http::response(['items' => [], 'nextCursor' => null])]);

        Tembo::sessions()->messages('s1', ['limit' => 10]);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tembo.io/v1/messages?sessionId=s1&limit=10');
    }

    public function test_state_reads_the_current_state(): void
    {
        Http::fake(['*' => Http::response($this->fakeSession('inProgress'))]);

        $this->assertSame(SessionState::IN_PROGRESS, Tembo::sessions()->state('s1'));
    }

    public function test_session_state_falls_back_to_the_flags(): void
    {
        $this->assertSame('failed', SessionState::of(['state' => ['isFailed' => true]]));
        $this->assertSame('queued', SessionState::of(['state' => ['isQueued' => true]]));
        $this->assertNull(SessionState::of([]));
        $this->assertFalse(SessionState::isFinished(['state' => ['inProgress' => true]]));
        $this->assertTrue(SessionState::isFinished(['state' => ['current' => 'cancelled']]));
    }

    public function test_wait_polls_until_the_session_finishes(): void
    {
        Sleep::fake();
        Http::fake(['*' => Http::sequence()
            ->push($this->fakeSession('queued'))
            ->push($this->fakeSession('inProgress'))
            ->push($this->fakeSession('completed'))]);

        $session = Tembo::sessions()->wait('s1', timeout: 600, interval: 5);

        $this->assertSame('completed', SessionState::of($session));
        Http::assertSentCount(3);
        Sleep::assertSleptTimes(2);
    }

    public function test_wait_gives_up_after_the_timeout(): void
    {
        Sleep::fake();
        Http::fake(['*' => Http::response($this->fakeSession('inProgress'))]);

        try {
            Tembo::sessions()->wait('s1', timeout: 10, interval: 5);
            $this->fail('No exception thrown.');
        } catch (SessionTimeoutException $e) {
            $this->assertSame('inProgress', SessionState::of($e->session));
            $this->assertStringContainsString('did not finish within 10 seconds', $e->getMessage());
        }

        Http::assertSentCount(3);
    }

    public function test_set_environment_variables_accepts_key_value_pairs(): void
    {
        Http::fake(['*' => Http::response(['items' => []])]);

        Tembo::projects()->setEnvironmentVariables('p1', ['APP_ENV' => 'testing', 'DEBUG' => 'true']);

        Http::assertSent(fn (Request $request) => $request->data() === ['secrets' => [
            ['key' => 'APP_ENV', 'value' => 'testing'],
            ['key' => 'DEBUG', 'value' => 'true'],
        ]]);
    }

    public function test_find_by_name_matches_owner_and_name(): void
    {
        Http::fake(['*' => Http::response(['items' => [
            ['id' => 'r1', 'owner' => 'acme', 'name' => 'api-gateway'],
            ['id' => 'r2', 'owner' => 'acme', 'name' => 'api'],
        ], 'nextCursor' => null])]);

        $this->assertSame('r2', Tembo::repositories()->findByName('Acme/API')['id'] ?? null);
        $this->assertSame('r2', Tembo::repositories()->findByName('api')['id'] ?? null);
        $this->assertNull(Tembo::repositories()->findByName('other/api'));

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'search=api'));
    }

    public function test_agent_runs_send_the_payload_as_custom_input(): void
    {
        Http::fake(['*' => Http::response(['id' => 'run1', 'status' => 'queued'])]);

        Tembo::agents()->run('a1', ['issue' => 42]);
        Tembo::agents()->run('a1');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tembo.io/v1/agents/a1/runs' && $request->body() === '{"issue":42}');
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tembo.io/v1/agents/a1/runs' && $request->body() === '{}');
    }
}
