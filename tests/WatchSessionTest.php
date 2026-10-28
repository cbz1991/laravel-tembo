<?php

namespace Cabuz\Tembo\Tests;

use Cabuz\Tembo\Events\SessionCancelled;
use Cabuz\Tembo\Events\SessionCompleted;
use Cabuz\Tembo\Events\SessionFailed;
use Cabuz\Tembo\Events\SessionFinished;
use Cabuz\Tembo\Events\SessionWatchTimedOut;
use Cabuz\Tembo\Facades\Tembo;
use Cabuz\Tembo\Jobs\WatchSession;
use Cabuz\Tembo\Tembo as TemboClient;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;

class WatchSessionTest extends TestCase
{
    public function test_watch_dispatches_a_job_with_the_context_and_config(): void
    {
        Queue::fake();
        config(['tembo.watch.queue' => 'tembo', 'tembo.watch.interval' => 20, 'tembo.watch.timeout' => 600]);
        $this->app->forgetInstance(TemboClient::class);
        Tembo::clearResolvedInstances();

        Tembo::sessions()->watch('s1', ['ticket_id' => 7]);

        Queue::assertPushedOn('tembo', WatchSession::class, fn (WatchSession $job) => $job->sessionId === 's1'
            && $job->context === ['ticket_id' => 7]
            && $job->interval === 20
            && abs($job->deadline - (time() + 600)) <= 2
            && $job->apiKey === null);
    }

    public function test_watch_carries_a_per_tenant_api_key_in_an_encrypted_job(): void
    {
        Queue::fake();

        Tembo::withApiKey('tenant-key')->sessions()->watch('s1');

        Queue::assertPushed(WatchSession::class, fn (WatchSession $job) => $job->apiKey === 'tenant-key');
        $this->assertInstanceOf(ShouldBeEncrypted::class, new WatchSession('s1'));
    }

    /**
     * @return array<string, array{string, class-string<SessionFinished>}>
     */
    public static function outcomes(): array
    {
        return [
            'completed' => ['completed', SessionCompleted::class],
            'failed' => ['failed', SessionFailed::class],
            'cancelled' => ['cancelled', SessionCancelled::class],
        ];
    }

    /**
     * @param  class-string<SessionFinished>  $event
     */
    #[DataProvider('outcomes')]
    public function test_a_finished_session_fires_its_event(string $state, string $event): void
    {
        Event::fake();
        Queue::fake();
        Http::fake(['*' => Http::response($this->fakeSession($state, ['artifacts' => [
            ['pullRequests' => [['url' => 'https://github.com/acme/api/pull/1']]],
        ]]))]);

        (new WatchSession('s1', ['ticket_id' => 7], 30, time() + 600))->handle(app(TemboClient::class), app('events'));

        Event::assertDispatched($event, fn (SessionFinished $e) => $e->sessionId === 's1'
            && $e->context === ['ticket_id' => 7]
            && $e->state() === $state
            && $e->pullRequestUrls() === ['https://github.com/acme/api/pull/1']);
        Queue::assertNothingPushed();
    }

    public function test_one_listener_on_the_interface_receives_every_outcome(): void
    {
        $received = [];
        Event::listen(SessionFinished::class, function (SessionFinished $event) use (&$received) {
            $received[] = $event::class;
        });

        Http::fake(['*' => Http::sequence()
            ->push($this->fakeSession('completed'))
            ->push($this->fakeSession('failed'))]);

        (new WatchSession('s1', [], 30, time() + 600))->handle(app(TemboClient::class), app('events'));
        (new WatchSession('s2', [], 30, time() + 600))->handle(app(TemboClient::class), app('events'));

        $this->assertSame([SessionCompleted::class, SessionFailed::class], $received);
    }

    public function test_a_running_session_is_polled_again_later(): void
    {
        Event::fake();
        Queue::fake();
        Http::fake(['*' => Http::response($this->fakeSession('inProgress'))]);

        $job = (new WatchSession('s1', [], 45, time() + 600))->onQueue('tembo');
        $job->handle(app(TemboClient::class), app('events'));

        Event::assertNotDispatched(SessionFinished::class);
        Queue::assertPushedOn('tembo', WatchSession::class, fn (WatchSession $next) => $next->sessionId === 's1' && $next->delay === 45);
    }

    public function test_the_watch_stops_at_the_deadline(): void
    {
        Event::fake();
        Queue::fake();
        Http::fake(['*' => Http::response($this->fakeSession('inProgress'))]);

        (new WatchSession('s1', [], 30, time() - 1))->handle(app(TemboClient::class), app('events'));

        Event::assertDispatched(SessionWatchTimedOut::class);
        Queue::assertNothingPushed();
    }

    public function test_the_job_polls_with_the_tenant_key(): void
    {
        Event::fake();
        Http::fake(['*' => Http::response($this->fakeSession('completed'))]);

        (new WatchSession('s1', [], 30, time() + 600, 'tenant-key'))->handle(app(TemboClient::class), app('events'));

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer tenant-key'));
    }
}
