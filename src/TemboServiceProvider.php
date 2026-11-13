<?php

namespace Cabuz\Tembo;

use Cabuz\Tembo\Commands\CheckCommand;
use Cabuz\Tembo\Commands\FixSentryCommand;
use Cabuz\Tembo\Commands\RunCommand;
use Cabuz\Tembo\Support\Sentry;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

class TemboServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tembo.php', 'tembo');

        $this->app->singleton(Tembo::class, fn ($app) => new Tembo(
            $app->make(HttpFactory::class),
            (array) $app['config']->get('tembo', []),
        ));

        $this->app->alias(Tembo::class, 'tembo');

        $this->app->bind(Sentry::class, fn ($app) => new Sentry(
            $app->make(HttpFactory::class),
            (array) $app['config']->get('tembo.sentry', []),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tembo.php' => config_path('tembo.php'),
            ], 'tembo-config');

            $this->commands([
                CheckCommand::class,
                FixSentryCommand::class,
                RunCommand::class,
            ]);
        }
    }
}
