<?php

namespace Cabuz\Tembo\Tests;

use Cabuz\Tembo\Exceptions\ConnectionException;
use Cabuz\Tembo\Exceptions\LiveSubscriptionException;
use Cabuz\Tembo\Facades\Tembo;
use Cabuz\Tembo\Live\SocketFactory;
use Cabuz\Tembo\Tests\Fakes\FakeSocket;
use Cabuz\Tembo\Tests\Fakes\FakeSocketFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

class LiveTest extends TestCase
{
    private const DELTA = ['type' => 'delta', 'data' => ['event' => 'output', 'message' => '{"text":"hi"}']];

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
        Http::fake(['https://api.tembo.io/v1/live' => Http::sequence()
            ->push(['ticket' => 't1'])
            ->push(['ticket' => 't2'])
            ->push(['ticket' => 't3']),
        ]);
    }

    public function test_it_subscribes_after_the_connection_is_ready_and_yields_output(): void
    {
        $socket = new FakeSocket([
            ['type' => 'ready'],
            ['type' => 'ready', 'id' => 'events'],
            ['type' => 'event', 'id' => 'events', 'data' => self::DELTA],
        ]);
        $factory = $this->fakeSockets([$socket]);

        $frames = [];
        foreach (Tembo::sessions()->events()->live()->subscribe('s1') as $frame) {
            $frames[] = $frame;

            if ($frame['type'] === 'delta') {
                break;
            }
        }

        $this->assertSame([['type' => 'ready'], self::DELTA], $frames);
        $this->assertSame(['wss://api.tembo.io/v1/live?ticket=t1'], $factory->urls);
        $this->assertSame([[
            'op' => 'subscribe',
            'id' => 'events',
            'resource' => 'sessions.events',
            'input' => ['scope' => ['sessionId' => 's1']],
        ]], $socket->sent);
        $this->assertTrue($socket->closed);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST' && $request->url() === 'https://api.tembo.io/v1/live');
    }

    public function test_it_reconnects_with_a_fresh_ticket_after_the_connection_drops(): void
    {
        $factory = $this->fakeSockets([
            new FakeSocket([['type' => 'ready'], ['type' => 'ready', 'id' => 'events']]),
            new FakeSocket([['type' => 'ready'], ['type' => 'ready', 'id' => 'events'], ['type' => 'event', 'id' => 'events', 'data' => self::DELTA]]),
        ]);

        $frames = [];
        foreach (Tembo::live()->subscribe(['resource' => 'sessions.events', 'input' => ['scope' => ['sessionId' => 's1']]]) as $frame) {
            $frames[] = $frame['type'];

            if ($frame['type'] === 'delta') {
                break;
            }
        }

        $this->assertSame(['ready', 'ready', 'delta'], $frames);
        $this->assertSame([
            'wss://api.tembo.io/v1/live?ticket=t1',
            'wss://api.tembo.io/v1/live?ticket=t2',
        ], $factory->urls);
    }

    public function test_a_rejected_subscription_is_not_retried(): void
    {
        $factory = $this->fakeSockets([
            new FakeSocket([['type' => 'ready'], ['type' => 'error', 'id' => 'events', 'status' => 403, 'message' => 'Forbidden']]),
        ]);

        try {
            foreach (Tembo::sessions()->events()->live()->subscribe('s1') as $frame) {
                //
            }
            $this->fail('Expected a LiveSubscriptionException.');
        } catch (LiveSubscriptionException $e) {
            $this->assertSame(403, $e->getCode());
            $this->assertSame('Forbidden', $e->getMessage());
        }

        $this->assertCount(1, $factory->urls);
    }

    public function test_it_gives_up_after_max_retries(): void
    {
        $factory = $this->fakeSockets([new FakeSocket([]), new FakeSocket([]), new FakeSocket([])]);

        $this->expectException(ConnectionException::class);

        try {
            foreach (Tembo::sessions()->events()->live()->subscribe('s1', ['maxRetries' => 2]) as $frame) {
                //
            }
        } finally {
            $this->assertCount(3, $factory->urls);
            Sleep::assertSequence([Sleep::for(500)->milliseconds(), Sleep::for(1000)->milliseconds()]);
        }
    }

    public function test_it_yields_idle_while_the_stream_is_quiet(): void
    {
        $this->fakeSockets([
            new FakeSocket([['type' => 'ready'], ['type' => 'ready', 'id' => 'events'], null]),
        ]);

        $frames = [];
        foreach (Tembo::sessions()->events()->live()->subscribe('s1') as $frame) {
            $frames[] = $frame['type'];

            if ($frame['type'] === 'idle') {
                break;
            }
        }

        $this->assertSame(['ready', 'idle'], $frames);
    }

    /**
     * @param  list<FakeSocket>  $sockets
     */
    private function fakeSockets(array $sockets): FakeSocketFactory
    {
        $factory = new FakeSocketFactory($sockets);
        $this->app->instance(SocketFactory::class, $factory);

        return $factory;
    }
}
