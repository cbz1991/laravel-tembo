<?php

namespace Cabuz\Tembo\Tests;

use Cabuz\Tembo\Facades\Tembo;
use Cabuz\Tembo\TemboServiceProvider;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function getPackageProviders($app): array
    {
        return [TemboServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Tembo' => Tembo::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('tembo.api_key', 'test-key');
        $app['config']->set('tembo.retry_delay', 0);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function fakeSession(string $current, array $extra = []): array
    {
        return [
            'id' => '11111111-1111-4111-8111-111111111111',
            'title' => 'Fix the login bug',
            'state' => [
                'current' => $current,
                'isQueued' => $current === 'queued',
                'inProgress' => $current === 'inProgress',
                'isCompleted' => $current === 'completed',
                'isFailed' => $current === 'failed',
                'isCancelled' => $current === 'cancelled',
            ],
        ] + $extra;
    }
}
