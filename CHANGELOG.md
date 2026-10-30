# Changelog

All notable changes to `laravel-tembo` will be documented in this file.

## Unreleased

## 1.0.9 - 2026-10-30

- `tembo:run --follow` prints readable output (the agent's text, tool calls
  with their output, setup steps) instead of raw provider events, for Claude
  Code, Codex, OpenCode, Pi and fx. `--raw` keeps the old output.
  `Cabuz\Tembo\Support\LiveOutput` does the rendering for your own code.

## 1.0.8 - 2026-10-28

- `Tembo::fakeLive()` fakes the live stream in tests: `subscribe()` yields
  `ready`, the given output events, then `idle` frames, and
  `assertSubscribed()` checks the subscription. `Tembo::fake()` now also
  answers the live ticket request.

## 1.0.7 - 2026-10-28

- A Laravel Boost guideline and skill (`resources/boost`), the counterpart of
  the official SDK's `SKILL.md`, so AI assistants use the package correctly.

## 1.0.6 - 2026-10-28

- `api.md` lists every method with the endpoint it calls, like the official
  SDK's API reference. It's generated from the code and checked by the tests.
- `Tembo::lastResponse()` returns the raw response of the most recent request,
  like the official SDK's `withResponse()`.
- 413 Payload Too Large throws `InvalidRequestException`.

## 1.0.5 - 2026-10-28

- Optional logging, like the official SDK's `TEMBO_LOG`: set `TEMBO_LOG` to
  `debug`, `info`, `warning` or `error` (and optionally `TEMBO_LOG_CHANNEL`)
  to log requests, retries and failures. Bodies and the API key are never
  logged.

## 1.0.4 - 2026-10-28

- `Tembo::withHeaders()`, `withQuery()`, `withTimeout()` and `withRetries()`
  return a copy of the client with that option changed, matching the official
  SDK's client and request options. New `headers` and `query` config keys add
  defaults for every request.

## 1.0.3 - 2026-10-28

- `users()->profilePicture()->update()` uploads a profile picture from an
  uploaded file, a file object, a stream or the raw bytes. This was the last
  REST endpoint without an SDK method.
- `Tembo::upload()` sends any multipart/form-data request.

## 1.0.2 - 2026-10-28

- Live streaming of a session's output over Tembo's WebSocket API, like the
  official SDK: `Tembo::sessions()->events()->live()->subscribe($id)` yields
  `ready`, `delta` and `idle` frames and reconnects with a fresh ticket.
  `Tembo::live()` exposes `authorize()`, `connect()` and `subscribe()` for
  manual multiplexing. Needs the optional `phrity/websocket` package.
- `tembo:run --follow` prints the agent's output until the session finishes.

## 1.0.1 - 2026-10-28

- **Breaking:** resources now have the same structure and method names as
  Tembo's official TypeScript SDK. Nested resources have their own accessor
  (`Tembo::agents()->runs()->list($agentId)` instead of `listRuns()`), and
  `get()` is now `retrieve()`. Other renames: `sessions()->events()` is
  `listEvents()`, `billing()->usage()` is `listUsage()`, `insights()->members()`
  and `repositories()` are `listMembers()` and `listRepositories()`, and the
  integration lookups are `retrieveSyncStatus()`, `retrieveRateLimit()`,
  `listSentryEnvironments()` and `listSlackChannels()`. `sessions()->messages()`
  is removed; use `messages()->list(['sessionId' => $id])`.
- `sessions()->stop()` accepts `cancelQueued`, `integrations()->authorize()`
  accepts `returnUrl` and `capability`, `integrations()->syncAll()` and
  `users()->delete()` accept a body, and `retrieveSyncStatus()` accepts an
  `integrationId` filter.
- `Tembo::delete()` accepts a request body.

## 1.0.0 - 2026-10-28

- First release.
- Client for the whole Tembo public API: sessions, messages, agents (runs,
  schedules, triggers, templates, revisions), projects (environment variables,
  snapshots, builds, schedules), repositories, pull requests, artifacts,
  integrations, MCP connections, skills, models, runtimes, organizations,
  users, API keys, billing, insights and pins. Raw `get()` / `post()` /
  `put()` / `patch()` / `delete()` for anything else.
- `Sessions::start()`, `sendMessage()`, `wait()` and `watch()`. `watch()`
  polls from a queued job and fires `SessionCompleted`, `SessionFailed`,
  `SessionCancelled` or `SessionWatchTimedOut`.
- Cursor pagination with `CursorPage::lazy()` across all pages.
- Typed exceptions per HTTP status; retries for rate limits on every request
  and for server and connection errors on reads only.
- `Tembo::withApiKey()` for one key per tenant.
- `Tembo::fake()` for tests.
- `tembo:check` and `tembo:run` Artisan commands.
