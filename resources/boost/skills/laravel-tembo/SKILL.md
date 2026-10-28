---
name: laravel-tembo
description: "Use when writing Laravel code that calls the Tembo API with cabuz/laravel-tembo: starting coding agents, following sessions, reacting to finished sessions, automation agents, projects, repositories, live output, errors and tests."
---

# Laravel Tembo

`cabuz/laravel-tembo` is a Laravel SDK for the Tembo API. Its structure and method names mirror Tembo's official TypeScript SDK; the exact signature and endpoint of every method is in the package's `api.md` (`vendor/cabuz/laravel-tembo/api.md`). Look it up instead of guessing.

## Setup

```dotenv
TEMBO_API_KEY=your-key
```

Check the key with `php artisan tembo:check`. Use the `Cabuz\Tembo\Facades\Tembo` facade or inject `Cabuz\Tembo\Tembo`. `Tembo::withApiKey($key)` returns a copy that uses another key (one per tenant).

## Conventions

- Methods take arrays (request body or query filters) and return the decoded JSON as an array.
- List methods return a `Cabuz\Tembo\Support\CursorPage`: iterate it for the current page, `nextPage()` for the next one, `lazy()` for every item across pages. Array filters become comma-separated values.
- Nested resources are reached through accessors and take the parent ID first:

```php
Tembo::agents()->runs()->list($agentId);
Tembo::sessions()->diffs()->retrieve($sessionId, $diffId);
Tembo::organizations()->secrets()->create($organizationId, ['key' => 'NPM_TOKEN', 'value' => '...']);
```

## Start an agent and react when it finishes

```php
use Cabuz\Tembo\Facades\Tembo;

$repository = Tembo::repositories()->findByName('acme/api');

$session = Tembo::sessions()->start('Fix the N+1 query on the orders page', [
    'codeRepositoryIds' => [$repository['id']],
]);

Tembo::sessions()->watch($session['id'], ['ticket_id' => $ticket->id]);
```

`watch()` dispatches a queued job that polls the session and fires one of `Cabuz\Tembo\Events\SessionCompleted`, `SessionFailed`, `SessionCancelled` or `SessionWatchTimedOut`. All implement `Cabuz\Tembo\Events\SessionFinished`. Each event has `$sessionId`, `$session`, `$context` (what was passed to `watch()`), `state()` and `pullRequestUrls()`.

```php
public function handle(SessionCompleted $event): void
{
    Ticket::find($event->context['ticket_id'])
        ->update(['pull_request' => $event->pullRequestUrls()[0] ?? null]);
}
```

Tembo has no webhooks, so don't build a webhook route for it. `Tembo::sessions()->wait($id)` blocks until the session ends; use it only in commands or jobs.

Follow up with `Tembo::sessions()->sendMessage($id, 'Also add a test')` (`['deliveryMode' => 'steer']` to steer the running turn), stop with `stop($id)`, retry differently with `fork($id, ['prompt' => '...'])`.

## Automation agents

```php
$agent = Tembo::agents()->create(['name' => 'Dependency updater', 'instructions' => '...', 'repositoryIds' => [$repositoryId]]);
Tembo::agents()->schedules()->create($agent['id'], ['cron' => '0 6 * * 1']);
Tembo::agents()->run($agent['id'], ['reason' => 'Security advisory']);
```

## Live output

Needs `composer require phrity/websocket`.

```php
foreach (Tembo::sessions()->events()->live()->subscribe($sessionId) as $frame) {
    // ['type' => 'ready'], ['type' => 'delta', 'data' => ['event' => ..., 'message' => ...]] or ['type' => 'idle']
}
```

Break out of the loop to stop. `idle` arrives after 15 seconds without output, so the loop can check `Tembo::sessions()->state($id)`.

## Errors

Every exception extends `Cabuz\Tembo\Exceptions\TemboException` (`status()`, `error()`, `$response`): `InvalidRequestException` (400, 413, 422), `AuthenticationException` (401), `PermissionDeniedException` (403), `NotFoundException` (404), `ConflictException` (409), `RateLimitException` (429), `ServerException` (5xx), `ConnectionException`, `MissingApiKeyException`. Rate limits are retried automatically; writes are never retried after a 5xx, so a failed `start()` should not be blindly repeated.

## Tests

```php
Tembo::fake([
    'v1/sessions' => ['id' => 'session-1'],
    'v1/repositories*' => ['items' => [['id' => 'r1', 'owner' => 'acme', 'name' => 'api']], 'nextCursor' => null],
]);

Http::assertSent(fn (Request $request) => $request->url() === 'https://api.tembo.io/v1/sessions'
    && $request['description'] === 'Fix the login bug');
```

Unmatched Tembo requests get an empty 200. Use `Queue::fake()` / `Event::fake()` for `watch()` and its events.
