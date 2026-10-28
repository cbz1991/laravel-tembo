# Laravel Tembo

`cabuz/laravel-tembo` calls the Tembo API, which runs coding agents (Claude Code, Codex, Cursor...) in the background on repositories. Use it instead of hand-written HTTP calls to Tembo.

- Use the `Cabuz\Tembo\Facades\Tembo` facade or inject `Cabuz\Tembo\Tembo`. The API key comes from `TEMBO_API_KEY`.
- Structure and method names follow Tembo's official TypeScript SDK: `list()`, `create()`, `retrieve()`, `update()`, `delete()`, and nested resources through accessors that take the parent ID first, e.g. `Tembo::agents()->runs()->retrieve($agentId, $runId)`. Look up exact signatures in the package's `api.md`; don't guess method names.
- Methods take arrays and return the decoded JSON as arrays; list methods return a `CursorPage` (`lazy()` streams every page).
- Start an agent with `Tembo::sessions()->start($task, ['codeRepositoryIds' => [...]])`. React to the result with `Tembo::sessions()->watch($id, $context)` and listeners for `SessionCompleted`, `SessionFailed`, `SessionCancelled` (or the `SessionFinished` interface). Never block a web request with `wait()`.
- Errors extend `Cabuz\Tembo\Exceptions\TemboException`. In tests use `Tembo::fake([...])` and `Http::assertSent()`.
