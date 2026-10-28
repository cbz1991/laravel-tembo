<?php

namespace Cabuz\Tembo\Tests;

use Cabuz\Tembo\Exceptions\LiveSubscriptionException;
use Cabuz\Tembo\Facades\Tembo;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class FakeTest extends TestCase
{
    public function test_fake_stubs_paths_relative_to_the_api(): void
    {
        Tembo::fake([
            'v1/sessions/*' => ['id' => 's1', 'title' => 'Faked'],
            'v1/users/me' => Http::response(['error' => 'Nope'], 401),
        ]);

        $this->assertSame('Faked', Tembo::sessions()->retrieve('s1')['title']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tembo.io/v1/sessions/s1');
    }

    public function test_unmatched_tembo_requests_get_an_empty_response(): void
    {
        Tembo::fake();

        $this->assertSame([], Tembo::billing()->retrieve());
    }

    public function test_fake_works_without_an_api_key(): void
    {
        config(['tembo.api_key' => null]);
        $this->app->forgetInstance(\Cabuz\Tembo\Tembo::class);
        Tembo::clearResolvedInstances();

        Tembo::fake(['v1/users/me' => ['id' => 'u1']]);

        $this->assertSame(['id' => 'u1'], Tembo::me());
    }

    public function test_fake_live_streams_the_given_output(): void
    {
        Tembo::fake();
        $live = Tembo::fakeLive([
            ['event' => 'output', 'message' => 'Reading the tests'],
            ['type' => 'delta', 'data' => ['event' => 'output', 'message' => 'Done']],
        ]);

        $frames = [];
        foreach (Tembo::sessions()->events()->live()->subscribe('s1') as $frame) {
            $frames[] = $frame;

            if ($frame['type'] === 'idle') {
                break;
            }
        }

        $this->assertSame([
            ['type' => 'ready'],
            ['type' => 'delta', 'data' => ['event' => 'output', 'message' => 'Reading the tests']],
            ['type' => 'delta', 'data' => ['event' => 'output', 'message' => 'Done']],
            ['type' => 'idle'],
        ], $frames);
        $live->assertSubscribed('s1');
    }

    public function test_fake_live_works_without_fake_and_fails_instead_of_hanging(): void
    {
        Tembo::fakeLive();

        $this->expectException(LiveSubscriptionException::class);

        foreach (Tembo::sessions()->events()->live()->subscribe('s1') as $frame) {
            // Never breaks.
        }
    }
}
