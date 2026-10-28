# Contributing

Thanks for considering a contribution to `laravel-tembo`! Bug reports,
fixes, new endpoints and documentation improvements are all welcome.

Please read this guide before opening an issue or a pull request. By taking
part in this project you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).

## Before you start

- **Bugs:** search the [existing issues](https://github.com/cbz1991/laravel-tembo/issues)
  first. If nobody has reported it yet, open a bug report using the template.
- **New features:** open a feature request and discuss it *before* writing code.
  This package is deliberately small and focused; an agreed design saves you
  from a pull request that can't be merged.
- **Security issues:** do **not** open a public issue. Follow the
  [security policy](SECURITY.md) instead.

## Staying in sync with the API

Tembo's API changes often. The source of truth is its
[OpenAPI schema](https://www.tembo.io/openapi.json):

```bash
bin/update-endpoints   # refresh tests/Fixtures/endpoints.json (needs curl and jq)
composer test
```

`EndpointCoverageTest` calls every SDK method and fails when a request doesn't
match a documented endpoint, or when an endpoint has no SDK method. Add the
missing methods, or list the endpoint in `NOT_WRAPPED` with a reason.

When you add a method:

- Put it on the resource that matches the endpoint's tag in the schema.
- Follow the naming: `list()`, `create()`, `get()`, `update()`, `delete()`,
  and `listX()` / `createX()` / `getX()` / `updateX()` / `deleteX()` for
  nested resources.
- Take request bodies and filters as arrays and return the decoded JSON, so
  new fields Tembo adds work without a release.
- Never retry a write after a server or connection error: it may already have
  started an agent.

## Development setup

Requirements: PHP 8.3+ and Composer for the full toolchain (the package itself
supports PHP 8.1+).

```bash
git clone https://github.com/<your-username>/laravel-tembo.git
cd laravel-tembo
composer install
composer test      # PHPUnit
composer analyse   # PHPStan (Larastan), level 6
composer lint      # check code style with Pint
composer format    # fix code style with Pint
```

Tests run on [Orchestra Testbench](https://github.com/orchestral/testbench)
with the HTTP client faked, so you don't need a Tembo account. Stray requests
are blocked, so a test can never reach the real API.

## Supported versions

Every change must work on the full support matrix:

| Laravel | PHP |
|---|---|
| 10 | 8.1, 8.2, 8.3 |
| 11 | 8.2, 8.3, 8.4 |
| 12 | 8.2, 8.3, 8.4, 8.5 |
| 13 | 8.3, 8.4, 8.5 |

CI runs every combination on each pull request. To check a specific Laravel
version locally:

```bash
composer require --dev "laravel/framework:10.*" --no-update
composer update --with-all-dependencies
composer test
```

(Revert `composer.json` afterwards; don't commit the pinned version.)

Concretely, this means:

- No syntax newer than PHP 8.1 (no `readonly` classes, no DNF types, no typed
  class constants).
- No Laravel API that doesn't exist in Laravel 10. If you need one, guard it
  with `method_exists()` / `class_exists()` and degrade gracefully.

## Coding guidelines

- Code style is enforced by [Pint](https://laravel.com/docs/pint) with the
  Laravel preset; run `composer format` before committing.
- Code must pass PHPStan (Larastan) at level 6. Fix the underlying type issue
  rather than adding ignores or baseline entries.
- Match the surrounding code: naming, comment density and structure.
- Any new config option needs a sensible default and an inline comment in
  `config/tembo.php` explaining it.
- Don't add new runtime dependencies to `require`.
- Never log, echo or put the API key in an exception message or event.
- Public API changes (facade methods, config keys, command names, exception
  classes) need a clear reason, since they affect every user.

## Tests

- Every bug fix needs a test that fails without the fix.
- Every new helper or option needs tests; plain endpoint wrappers are covered
  by `EndpointCoverageTest`.
- Fake the API with `Http::fake()` and assert the exact URL and body sent.

## Pull requests

1. Fork the repository and create a branch from `main`
   (e.g. `fix/pagination-cursor`, `feature/session-artifacts`).
2. Make your change, with tests.
3. Run `composer test`, `composer analyse` and `composer lint`, and make sure
   everything passes (`composer format` fixes style issues).
4. Add a line under an `## Unreleased` heading at the top of
   [`CHANGELOG.md`](CHANGELOG.md) describing the change from a user's point of
   view.
5. Update `README.md` if the behaviour or configuration changed.
6. Open the pull request against `main` and fill in the template.

Guidelines for a pull request that is easy to review and merge:

- **One change per pull request.** Split unrelated fixes into separate ones.
- **Small and focused.** Don't mix refactoring or reformatting with a fix.
- **Clear commit messages** in the imperative mood, with a short summary line,
  e.g. `Add the session diffs endpoints`.
- **Green CI.** Pull requests with failing checks won't be reviewed until they
  pass.

## Releases

Releases are handled by the maintainer. Don't change version numbers or create
tags in your pull request; the maintainer decides the version (following
[Semantic Versioning](https://semver.org/)) and moves the `Unreleased`
changelog entries under it.

## License

By contributing, you agree that your contributions will be licensed under the
project's [MIT License](LICENSE).
