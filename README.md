# laravel-tembo

[![tests](https://github.com/cbz1991/laravel-tembo/actions/workflows/tests.yml/badge.svg)](https://github.com/cbz1991/laravel-tembo/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/cabuz/laravel-tembo)](https://packagist.org/packages/cabuz/laravel-tembo)
[![Total Downloads](https://img.shields.io/packagist/dt/cabuz/laravel-tembo)](https://packagist.org/packages/cabuz/laravel-tembo)
[![License](https://img.shields.io/packagist/l/cabuz/laravel-tembo)](LICENSE)

A Laravel SDK for the [Tembo](https://www.tembo.io) API.

Tembo runs coding agents (Claude Code, Codex, Cursor, Amp, Opencode...) in the
background on your repositories, and they usually finish with a pull request.
This package lets your Laravel app start those agents and react when they
finish:

```php
use Cabuz\Tembo\Facades\Tembo;

$session = Tembo::sessions()->start('Fix the N+1 query on the orders page', [
    'codeRepositoryIds' => [$repositoryId],
]);

Tembo::sessions()->watch($session['id'], ['ticket_id' => $ticket->id]);
```

```php
// In a listener: the agent is done.
public function handle(SessionCompleted $event): void
{
    Ticket::find($event->context['ticket_id'])
        ->update(['pull_request' => $event->pullRequestUrls()[0] ?? null]);
}
```

- **The whole public API**: sessions, messages, agents (runs, schedules,
  triggers), projects, repositories, pull requests, integrations, MCP
  connections, skills, organizations, users, billing and more. A test checks
  every SDK method against Tembo's OpenAPI schema.
- **Laravel-native**: config file, facade, Laravel's HTTP client, so
  `Http::fake()` and `Tembo::fake()` work in your tests.
- **Session events without webhooks**: a queued job watches a session and
  fires `SessionCompleted`, `SessionFailed` or `SessionCancelled`.
- **Live output**: stream what the agent is doing over Tembo's WebSocket API,
  with automatic reconnects.
- **Safe retries**: rate limits are retried for every request, but server
  errors and dropped connections only for reads, so a retry never starts the
  same agent twice.
- **Typed exceptions** for every error status, and **cursor pagination** that
  can stream every page lazily.
- **Artisan commands** to check your key and start an agent from the terminal.

> This is a community package, not an official Tembo SDK. Tembo's own SDK is
> TypeScript only.

## Requirements

- PHP 8.1+
- Laravel 10.10+, 11, 12 or 13
- A Tembo API key

## Installation

```bash
composer require cabuz/laravel-tembo
```

Create an API key in the Tembo dashboard under **Settings > API Keys** and add
it to `.env`:

```dotenv
TEMBO_API_KEY=your-key
```

Check that it works:

```bash
php artisan tembo:check
```

To change the defaults, publish the config file:

```bash
php artisan vendor:publish --tag=tembo-config
```

## Usage

Use the `Tembo` facade, or inject `Cabuz\Tembo\Tembo`. Every method returns the
decoded JSON as an array; list methods return a [`CursorPage`](#pagination).

### Start a coding agent

```php
$session = Tembo::sessions()->start('Add rate limiting to the public API', [
    'codeRepositoryIds' => ['123e4567-e89b-12d3-a456-426614174000'],
    'agent' => 'claudeCode:claude-fable-5-1', // optional, see Tembo::models()->list()
    'baseBranch' => 'develop',
]);

$session['id'];      // session UUID
$session['htmlUrl']; // link to the session in Tembo
```

`start()` creates the session and queues the task right away. Use
`create()` to pass the raw request body yourself.

Look up repository IDs by name:

```php
$repository = Tembo::repositories()->findByName('acme/api');
```

### Follow up, steer or stop

```php
Tembo::sessions()->sendMessage($id, 'Also add a feature test');
Tembo::sessions()->sendMessage($id, 'Use the existing RateLimiter', ['deliveryMode' => 'steer']);

Tembo::sessions()->stop($id);
Tembo::sessions()->fork($id, ['prompt' => 'Try a different approach']);
```

### Wait for the result

Tembo has no webhooks yet. There are two ways to know when an agent is done.

**In a queue (recommended).** `watch()` dispatches a job that polls the session
every 30 seconds and fires an event when it ends. No worker is blocked while
the agent runs.

```php
Tembo::sessions()->watch($session['id'], context: ['ticket_id' => 42]);
```

| Event | When |
|---|---|
| `Cabuz\Tembo\Events\SessionCompleted` | The agent finished. |
| `Cabuz\Tembo\Events\SessionFailed` | The agent failed. |
| `Cabuz\Tembo\Events\SessionCancelled` | The session was stopped. |
| `Cabuz\Tembo\Events\SessionWatchTimedOut` | Still running after the watch timeout (1 hour by default). |

Every event has `$sessionId`, `$session` (the latest API data), `$context`
(what you passed to `watch()`), `state()` and `pullRequestUrls()`. All four
implement `Cabuz\Tembo\Events\SessionFinished`, so one listener on that
interface receives every outcome.

**Synchronously.** `wait()` polls in the current process and returns the final
session. Use it in commands and jobs, not in web requests.

```php
$session = Tembo::sessions()->wait($id, timeout: 900, interval: 15);

\Cabuz\Tembo\Support\SessionState::of($session); // "completed", "failed" or "cancelled"
```

It throws `SessionTimeoutException` when the timeout passes.

### Stream the live output

Follow what the agent is doing as it happens, over Tembo's WebSocket API.
This needs a WebSocket client:

```bash
composer require phrity/websocket
```

```php
foreach (Tembo::sessions()->events()->live()->subscribe($sessionId) as $frame) {
    // "ready" once subscribed, "delta" for each output event,
    // "idle" after 15 seconds without output.
    if ($frame['type'] === 'delta') {
        logger()->info($frame['data']['message']);
    }

    // Break whenever you're done; the connection is closed for you.
}
```

`delta` frames carry `event`, `message` (the agent provider's event payload as
a string) and, when known, `provider`, `messageId` and `createdAt`. `idle`
frames are specific to this package: PHP can't run a timer during a blocking
read, so the loop gets a turn to check other things, such as
`Tembo::sessions()->state($id)`, while the agent is quiet.

Dropped connections are retried with a new ticket (up to 5 times, with
backoff); a rejected subscription throws `LiveSubscriptionException`. It's a
live stream, not a replay: use `Tembo::sessions()->listEvents($id)` for
anything missed during a reconnect. Options: `maxRetries`, `initialDelay` and
`maxDelay` (milliseconds) and `timeout` (seconds).

For several subscriptions on one connection, use the raw API:
`Tembo::live()->authorize()` returns a ticket, `Tembo::live()->connect($ticket)`
opens the connection, and `send()` / `receive()` exchange
[the protocol's](https://github.com/tembo/sdk#websockets) frames.

### Automation agents

```php
$agent = Tembo::agents()->create([
    'name' => 'Dependency updater',
    'instructions' => 'Update outdated Composer packages and open a PR.',
    'repositoryIds' => [$repositoryId],
]);

Tembo::agents()->schedules()->create($agent['id'], ['cron' => '0 6 * * 1']);

// Run it now; the payload reaches the agent as custom input.
$run = Tembo::agents()->run($agent['id'], ['reason' => 'Security advisory CVE-2026-1234']);
```

### Everything else

The structure and method names follow Tembo's official
[TypeScript SDK](https://github.com/tembo/sdk/blob/main/api.md).
[api.md](api.md) lists every method with the endpoint it calls. Each resource has `list()`, `create()`,
`retrieve()`, `update()` and `delete()` where the API supports them. Nested
resources are reached through an accessor and take the parent ID first:

```php
Tembo::agents()->runs()->retrieve($agentId, $runId);
Tembo::sessions()->diffs()->list($sessionId);
Tembo::organizations()->secrets()->create($organizationId, ['key' => 'NPM_TOKEN', 'value' => '...']);
Tembo::projects()->environmentVariables()->create($projectId, ['APP_ENV' => 'testing']);
Tembo::users()->profilePicture()->update($userId, $request->file('avatar'));
```

| Accessor | Nested resources and extra methods |
|---|---|
| `sessions()` | `sources()`, `diffs()`, `inputs()`, `queue()`, `commands()`; `fork()`, `stop()`, `listEvents()` |
| `messages()` | Session messages |
| `agents()` | `templates()`, `schedules()`, `triggers()`, `runs()`, `contentRevisions()` |
| `projects()` | `environmentVariables()`, `snapshots()`, `builds()`, `schedules()`; `duplicate()`, `updateDefaults()` |
| `repositories()` | Repositories |
| `pullRequests()` | Pull requests opened by sessions |
| `artifacts()` | Session artifacts |
| `integrations()` | `providers()`, `triggers()`; `authorize()`, `test()`, `sync()`, `syncAll()`, `retrieveSyncStatus()`, `retrieveRateLimit()`, `listSentryEnvironments()`, `listSlackChannels()` |
| `mcpConnections()` | `authorize()`, `test()` |
| `skills()` | `marketplace()` |
| `models()`, `runtimes()` | Available agent models and runtimes |
| `organizations()` | `members()`, `secrets()`, `settings()`, `subscriptions()` |
| `users()` | `settings()`, `connectedAccounts()`, `subscriptions()`, `profilePicture()`; `me()` |
| `apiKeys()` | API keys |
| `billing()` | `retrieve()`, `listUsage()` |
| `insights()` | `retrieve()`, `update()`, `listMembers()`, `listRepositories()` |
| `pins()` | Pins |

For an endpoint Tembo adds before this package wraps it, use the raw methods:

```php
Tembo::get('v1/some-new-endpoint', ['limit' => 10]);
Tembo::post('v1/some-new-endpoint', ['name' => 'x']);
```

### Pagination

List methods return a `CursorPage`:

```php
$page = Tembo::sessions()->list(['state' => 'active', 'limit' => 50]);

foreach ($page as $session) { /* items on this page */ }

$page->hasMore();
$next = $page->nextPage();

// Every item across all pages, fetched on demand:
Tembo::repositories()->list()->lazy()->each(fn ($repository) => ...);
```

List filters can be passed as PHP arrays and booleans; the package turns them
into the comma-separated values and `true`/`false` strings Tembo expects.

### Multiple API keys

Use `withApiKey()` to act as another Tembo organization, for example one per
tenant. It returns a copy and leaves the default client untouched.

```php
Tembo::withApiKey($tenant->tembo_key)->sessions()->start('...');
```

Watched sessions keep using that key. The key travels inside the queued job,
so the job payload is encrypted with your `APP_KEY`.

### Per-client options

Like the official SDK's client and request options, these return a copy with
one setting changed:

```php
Tembo::withTimeout(120)->sessions()->list();       // seconds
Tembo::withRetries(0)->agents()->run($agentId);    // no retries
Tembo::withHeaders(['X-Request-Id' => $requestId])->sessions()->retrieve($id);
Tembo::withQuery(['limit' => 100])->repositories()->list();
```

They can be chained, and `withConfig()` replaces any config value. The
`Authorization` header always comes from the API key. Default headers and query
parameters for every request go in the `headers` and `query` config keys.

## Errors

Every error extends `Cabuz\Tembo\Exceptions\TemboException`:

| Exception | Status |
|---|---|
| `InvalidRequestException` | 400, 413, 422 |
| `AuthenticationException` | 401 |
| `PermissionDeniedException` | 403 |
| `NotFoundException` | 404 |
| `ConflictException` | 409 |
| `RateLimitException` | 429, after retries (`retryAfter()` gives the seconds) |
| `ServerException` | 5xx |
| `ConnectionException` | Tembo could not be reached |
| `LiveSubscriptionException` | A live subscription was rejected (`getCode()` gives the status) |
| `MissingApiKeyException` | No API key configured |
| `MissingWebSocketClientException` | Live streaming without `phrity/websocket` installed |

```php
try {
    Tembo::sessions()->retrieve($id);
} catch (NotFoundException $e) {
    $e->error();    // Tembo's error message
    $e->status();   // 404
    $e->response;   // the Illuminate\Http\Client\Response
}
```

For a successful call, `Tembo::lastResponse()` returns the raw response of the
most recent request (status, headers such as rate limits, body), like the
official SDK's `withResponse()`.

## Retries

A request is retried up to 2 times (`TEMBO_RETRIES`), with a delay that
doubles after each attempt and follows `Retry-After` when Tembo sends it:

- **429 Too Many Requests**: retried for every request, since Tembo didn't
  process it.
- **5xx and connection errors**: retried for `GET` requests only. A failed
  `POST` may already have started an agent, so it is never repeated.

## Logging

Like the official SDK's `TEMBO_LOG`, set the lowest level to log:

```dotenv
TEMBO_LOG=debug
TEMBO_LOG_CHANNEL=stack   # optional, defaults to your default channel
```

| Level | Logs |
|---|---|
| `debug` | Every request: method, path, query, status and duration |
| `info` | Retries |
| `warning` | Failed requests (4xx) |
| `error` | Server errors (5xx) and connection failures |

Each level includes the ones below it. Request bodies and the API key are
never logged. Logging is off by default.

## Artisan commands

```bash
# Check the API key
php artisan tembo:check

# Start an agent; --repo takes an ID or owner/name and can be repeated
php artisan tembo:run "Fix the flaky CheckoutTest" --repo=acme/shop --wait

# Or print the agent's output while it works (needs phrity/websocket)
php artisan tembo:run "Fix the flaky CheckoutTest" --repo=acme/shop --follow
```

`--follow` shows the agent's text as it writes it, one line per tool call
with its output, and the setup steps:

```
· setup_finished completed
→ Bash: php artisan test --filter=CheckoutTest
  Tests:  1 passed
The test was flaky because...
```

It understands the formats of Claude Code, Codex, OpenCode, Pi and fx; add
`--raw` to print the provider events as they arrive instead. `tembo:run` also
accepts `--agent`, `--project`, `--branch` and `--timeout`.

To render live output yourself, pass each `delta` frame's data to
`Cabuz\Tembo\Support\LiveOutput::render()`; it returns the text to print.

## Testing your app

`Tembo::fake()` stubs the API with paths relative to it. Requests that don't
match get an empty `200`, so tests never reach Tembo. Assert on requests with
Laravel's `Http` assertions.

```php
use Cabuz\Tembo\Facades\Tembo;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

Tembo::fake([
    'v1/sessions' => ['id' => 'session-1', 'htmlUrl' => 'https://app.tembo.io/...'],
    'v1/sessions/*' => ['id' => 'session-1', 'state' => ['current' => 'completed']],
]);

// ... exercise your code ...

Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tembo.io/v1/sessions'
    && $request['description'] === 'Fix the login bug');
```

Patterns match the full URL, query string included, so use a trailing `*` for
requests with filters (`'v1/repositories*'`). Values can also be
`Http::response(...)` or `Http::sequence()` stubs. For the watcher, use
`Queue::fake()` and `Event::fake()` as usual.

`Tembo::fakeLive()` fakes the live stream: `subscribe()` yields `ready`, a
`delta` for each event you pass, then `idle` frames.

```php
$live = Tembo::fakeLive([
    ['event' => 'output', 'message' => 'Running the tests'],
]);

// ... exercise your code ...

$live->assertSubscribed($sessionId);
```

A loop that never breaks fails with `LiveSubscriptionException` after 100 idle
frames instead of hanging the test suite. For full control (dropped
connections, errors), bind your own `Cabuz\Tembo\Live\SocketFactory` in the
container.

## Configuration

| Key | `.env` | Default |
|---|---|---|
| `api_key` | `TEMBO_API_KEY` | none |
| `base_url` | `TEMBO_BASE_URL` | `https://api.tembo.io` |
| `timeout` | `TEMBO_TIMEOUT` | `30` seconds |
| `retries` | `TEMBO_RETRIES` | `2` |
| `retry_delay` | `TEMBO_RETRY_DELAY` | `500` ms |
| `watch.interval` | `TEMBO_WATCH_INTERVAL` | `30` seconds |
| `watch.timeout` | `TEMBO_WATCH_TIMEOUT` | `3600` seconds |
| `watch.connection` | `TEMBO_QUEUE_CONNECTION` | default connection |
| `watch.queue` | `TEMBO_QUEUE` | default queue |
| `log` | `TEMBO_LOG` | none (off) |
| `log_channel` | `TEMBO_LOG_CHANNEL` | default channel |
| `headers` | none | `[]` |
| `query` | none | `[]` |

## AI assistants

The package ships a [Laravel Boost](https://github.com/laravel/boost)
guideline and skill, like the `SKILL.md` of the official SDK. Run
`php artisan boost:install` (or `boost:update`) and select
`cabuz/laravel-tembo`, and your AI assistant learns how to call Tembo with this
package instead of guessing.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). When Tembo ships new endpoints,
`bin/update-endpoints` refreshes the schema snapshot and the test suite lists
what's missing.

## Security

Please report security issues privately, as described in
[SECURITY.md](SECURITY.md).

## License

The MIT License (MIT). See [LICENSE](LICENSE).
