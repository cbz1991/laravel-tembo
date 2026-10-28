# Changelog

All notable changes to `laravel-tembo` will be documented in this file.

## Unreleased

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
