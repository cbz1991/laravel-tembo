# Security Policy

`laravel-tembo` handles your Tembo API key, which can start coding agents on
your repositories. A bug that leaks the key (for example into logs, exception
messages or events) or sends requests somewhere other than the configured
Tembo API is treated as a security issue.

## Supported versions

Only the latest release receives security fixes.

## Reporting a vulnerability

Please **do not open a public issue** for security problems.

Report them privately through GitHub instead:

1. Go to the [Security tab](https://github.com/cbz1991/laravel-tembo/security)
   of the repository.
2. Click **Report a vulnerability**.
3. Describe the issue, the affected versions and, if possible, the steps to
   reproduce it.

You'll get a reply within a few days. Once the issue is confirmed, a fix is
released as soon as possible and you'll be credited in the changelog unless
you'd rather stay anonymous.

## Issues in Tembo itself

This policy covers the package only. If the problem is in the Tembo API or
service (for example an endpoint that exposes another organization's data),
report it to [Tembo](https://www.tembo.io) directly.
