<?php

namespace Cabuz\Tembo\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class FixSentryCommandTest extends TestCase
{
    private const REPO = '22222222-2222-4222-8222-222222222222';

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('tembo.sentry', [
            'token' => 'sentry-token',
            'url' => 'https://sentry.io',
            'organization' => 'acme',
            'project' => 'api',
            'repository' => self::REPO,
            'branch_prefix' => 'fix/sentry-',
        ]);
    }

    public function test_it_starts_one_session_per_issue_on_its_own_branch(): void
    {
        Http::fake([
            'sentry.io/api/0/projects/acme/api/issues/*' => Http::response([$this->issue('101', 'API-1A'), $this->issue('102', 'API-1B')]),
            'sentry.io/api/0/organizations/acme/issues/*/events/latest/' => Http::response($this->event()),
            'api.tembo.io/v1/sessions/*' => Http::response(['error' => 'Not found'], 404),
            'api.tembo.io/v1/sessions' => Http::response($this->fakeSession('queued', ['htmlUrl' => 'https://app.tembo.io/sessions/s1']), 201),
        ]);

        $this->artisan('tembo:fix-sentry')
            ->expectsOutputToContain('API-1A Undefined variable $user')
            ->expectsOutputToContain('https://app.tembo.io/sessions/s1')
            ->assertSuccessful();

        $created = Http::recorded(fn (Request $request) => $request->method() === 'POST')->map(fn ($pair) => $pair[0]->data())->values();

        $this->assertCount(2, $created);
        $this->assertSame(['fix/sentry-api-1a', 'fix/sentry-api-1b'], $created->pluck('branchName')->all());
        $this->assertSame([self::REPO], $created[0]['codeRepositoryIds']);
        $this->assertTrue($created[0]['queueRightAway']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $created[0]['id']);
        $this->assertNotSame($created[0]['id'], $created[1]['id']);

        $task = $created[0]['description'];
        $this->assertStringContainsString('Undefined variable $user', $task);
        $this->assertStringContainsString('https://sentry.io/organizations/acme/issues/101/ (42 events, 7 users', $task);
        $this->assertStringContainsString('Environment: production', $task);
        $this->assertStringContainsString('Exception: ErrorException: Undefined variable $user', $task);
        $this->assertStringContainsString("  app/Http/Controllers/ProfileController.php:18 in App\\Http\\Controllers\\ProfileController::show\n  app/Models/User.php:9 in App\\Models\\User::find", $task);
        $this->assertStringNotContainsString('vendor/laravel', $task);
        $this->assertStringContainsString('Add or update a test that fails without the fix', $task);

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://sentry.io/api/0/projects/acme/api/issues/')
            && $request['query'] === 'is:unresolved'
            && $request->hasHeader('Authorization', 'Bearer sentry-token'));
    }

    public function test_it_uses_the_same_session_id_for_an_issue_every_run(): void
    {
        $ids = [];

        foreach ([1, 2] as $run) {
            Http::fake([
                'sentry.io/api/0/projects/*' => Http::response([$this->issue('101', 'API-1A')]),
                'sentry.io/api/0/organizations/*' => Http::response($this->event()),
                'api.tembo.io/v1/sessions/*' => Http::response([], 404),
                'api.tembo.io/v1/sessions' => Http::response($this->fakeSession('queued'), 201),
            ]);

            $this->artisan('tembo:fix-sentry')->assertSuccessful();
            $ids[] = Http::recorded(fn (Request $request) => $request->method() === 'POST')->first()[0]['id'];
        }

        $this->assertSame($ids[0], $ids[1]);
    }

    public function test_it_skips_issues_that_already_have_a_session(): void
    {
        Http::fake([
            'sentry.io/api/0/projects/*' => Http::response([$this->issue('101', 'API-1A')]),
            'api.tembo.io/v1/sessions/*' => Http::response($this->fakeSession('inProgress')),
        ]);

        $this->artisan('tembo:fix-sentry')
            ->expectsOutputToContain('already sent')
            ->assertSuccessful();

        Http::assertNotSent(fn (Request $request) => $request->method() === 'POST');
    }

    public function test_dry_run_lists_the_issues_and_their_branches_without_starting_sessions(): void
    {
        Http::fake([
            'sentry.io/api/0/projects/*' => Http::response([$this->issue('101', 'API-1A')]),
            'api.tembo.io/v1/sessions/*' => Http::response([], 404),
        ]);

        $this->artisan('tembo:fix-sentry', ['--dry-run' => true, '--limit' => 1, '--query' => 'is:unresolved level:error'])
            ->expectsOutputToContain('fix/sentry-api-1a')
            ->assertSuccessful();

        Http::assertNotSent(fn (Request $request) => $request->method() === 'POST');
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'sentry.io') && $request['query'] === 'is:unresolved level:error' && (string) $request['limit'] === '1');
    }

    public function test_it_resolves_the_repository_option_by_name(): void
    {
        Http::fake([
            'api.tembo.io/v1/repositories*' => Http::response(['items' => [['id' => 'repo-1', 'owner' => 'acme', 'name' => 'web']], 'nextCursor' => null]),
            'sentry.io/*' => Http::response([]),
        ]);

        $this->artisan('tembo:fix-sentry', ['--repo' => ['acme/web']])
            ->expectsOutputToContain('No Sentry issues match [is:unresolved].')
            ->assertSuccessful();
    }

    public function test_it_fails_without_a_repository(): void
    {
        config(['tembo.sentry.repository' => null]);
        Http::fake();

        $this->artisan('tembo:fix-sentry')
            ->expectsOutputToContain('Pass --repo or set tembo.sentry.repository')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_it_fails_with_a_clear_message_when_sentry_is_not_configured(): void
    {
        config(['tembo.sentry.token' => null]);
        Http::fake();

        $this->artisan('tembo:fix-sentry')
            ->expectsOutputToContain('Set tembo.sentry.token')
            ->assertFailed();
    }

    public function test_it_reports_sentry_errors(): void
    {
        Http::fake(['sentry.io/*' => Http::response(['detail' => 'Invalid token'], 401)]);

        $this->artisan('tembo:fix-sentry')
            ->expectsOutputToContain('401')
            ->assertFailed();
    }

    /**
     * @return array<string, mixed>
     */
    private function issue(string $id, string $shortId): array
    {
        return [
            'id' => $id,
            'shortId' => $shortId,
            'title' => 'Undefined variable $user',
            'culprit' => 'App\Http\Controllers\ProfileController::show',
            'permalink' => "https://sentry.io/organizations/acme/issues/{$id}/",
            'count' => '42',
            'userCount' => 7,
            'lastSeen' => '2026-11-12T10:00:00Z',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function event(): array
    {
        return [
            'tags' => [['key' => 'environment', 'value' => 'production'], ['key' => 'browser', 'value' => 'Firefox']],
            'entries' => [
                ['type' => 'request', 'data' => ['url' => 'https://acme.test/profile']],
                ['type' => 'exception', 'data' => ['values' => [[
                    'type' => 'ErrorException',
                    'value' => 'Undefined variable $user',
                    'stacktrace' => ['frames' => [
                        ['filename' => 'vendor/laravel/framework/src/Illuminate/Routing/Controller.php', 'lineNo' => 54, 'function' => 'callAction', 'inApp' => false],
                        ['filename' => 'app/Models/User.php', 'lineNo' => 9, 'function' => 'App\Models\User::find', 'inApp' => true],
                        ['filename' => 'app/Http/Controllers/ProfileController.php', 'lineNo' => 18, 'function' => 'App\Http\Controllers\ProfileController::show', 'inApp' => true],
                    ]],
                ]]]],
            ],
        ];
    }
}
