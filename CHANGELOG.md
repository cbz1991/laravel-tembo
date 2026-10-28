# Changelog

All notable changes to `laravel-tembo` will be documented in this file.

## Unreleased

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
