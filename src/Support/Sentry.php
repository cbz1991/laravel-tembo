<?php

namespace Cabuz\Tembo\Support;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

/**
 * A small client for the Sentry API calls tembo:fix-sentry needs: a project's
 * issues and their latest event. Also turns an issue into a task for the agent.
 */
class Sentry
{
    /**
     * Stack frames included in the task.
     */
    public int $maxFrames = 15;

    /**
     * @param  array<string, mixed>  $config  The tembo.sentry config: token, url, organization, project.
     */
    public function __construct(protected HttpFactory $http, protected array $config) {}

    /**
     * The first issues of the project that match a Sentry search query.
     *
     * @return list<array<string, mixed>>
     */
    public function issues(string $query, int $limit): array
    {
        $issues = $this->request()
            ->get(sprintf('projects/%s/%s/issues/', rawurlencode($this->setting('organization')), rawurlencode($this->setting('project'))), [
                'query' => $query,
                'limit' => $limit,
            ])
            ->throw()
            ->json();

        return array_slice(array_values(array_filter((array) $issues, 'is_array')), 0, $limit);
    }

    /**
     * The most recent event of an issue, with its stack trace.
     *
     * @return array<string, mixed>
     */
    public function latestEvent(string $issueId): array
    {
        return (array) $this->request()
            ->get(sprintf('organizations/%s/issues/%s/events/latest/', rawurlencode($this->setting('organization')), rawurlencode($issueId)))
            ->throw()
            ->json();
    }

    /**
     * The task for the coding agent: the error, where it happened, and how to fix it.
     *
     * @param  array<string, mixed>  $issue
     * @param  array<string, mixed>  $event
     */
    public function task(array $issue, array $event): string
    {
        $lines = ['Fix this production error reported by Sentry.', '', (string) ($issue['title'] ?? 'Unknown error')];

        if (! empty($issue['culprit'])) {
            $lines[] = 'In: '.$issue['culprit'];
        }

        $seen = array_filter([
            isset($issue['count']) ? $issue['count'].' events' : null,
            isset($issue['userCount']) ? $issue['userCount'].' users' : null,
            isset($issue['lastSeen']) ? 'last seen '.$issue['lastSeen'] : null,
        ]);
        $lines[] = 'Sentry: '.($issue['permalink'] ?? $issue['shortId'] ?? $issue['id'] ?? '').($seen !== [] ? ' ('.implode(', ', $seen).')' : '');

        foreach ((array) ($event['tags'] ?? []) as $tag) {
            if (is_array($tag) && in_array($tag['key'] ?? null, ['environment', 'release', 'url'], true)) {
                $lines[] = ucfirst((string) $tag['key']).': '.$tag['value'];
            }
        }

        $exception = $this->exception($event);

        if ($exception !== null) {
            $lines[] = '';
            $lines[] = 'Exception: '.trim(($exception['type'] ?? '').': '.($exception['value'] ?? ''), ': ');

            $frames = $this->frames($exception);

            if ($frames !== []) {
                $lines[] = 'Stack trace (most recent call first):';

                foreach ($frames as $frame) {
                    $lines[] = '  '.$frame;
                }
            }
        }

        $lines[] = '';
        $lines[] = 'Find the root cause and fix it with the smallest change that solves it. Add or update a test that fails without the fix, and run the test suite. Don\'t change unrelated code. If the error can\'t be fixed in the code (bad data, a third-party outage), explain why instead of changing code.';

        return implode("\n", $lines);
    }

    /**
     * The exception that was thrown: the last one in the event's chain.
     *
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>|null
     */
    protected function exception(array $event): ?array
    {
        foreach ((array) ($event['entries'] ?? []) as $entry) {
            if (is_array($entry) && ($entry['type'] ?? null) === 'exception') {
                $values = array_values(array_filter((array) ($entry['data']['values'] ?? []), 'is_array'));

                return $values === [] ? null : end($values);
            }
        }

        return null;
    }

    /**
     * "file:line in function" for the exception's frames, most recent first,
     * keeping only the application's own frames when Sentry marked any.
     *
     * @param  array<string, mixed>  $exception
     * @return list<string>
     */
    protected function frames(array $exception): array
    {
        $frames = array_reverse(array_values(array_filter((array) ($exception['stacktrace']['frames'] ?? []), 'is_array')));
        $inApp = array_values(array_filter($frames, fn (array $frame) => ($frame['inApp'] ?? false) === true));

        return array_map(
            fn (array $frame) => ($frame['filename'] ?? $frame['absPath'] ?? '?').(isset($frame['lineNo']) ? ':'.$frame['lineNo'] : '').(isset($frame['function']) ? ' in '.$frame['function'] : ''),
            array_slice($inApp !== [] ? $inApp : $frames, 0, $this->maxFrames),
        );
    }

    protected function request(): PendingRequest
    {
        return $this->http
            ->baseUrl(rtrim((string) ($this->config['url'] ?? 'https://sentry.io'), '/').'/api/0/')
            ->withToken($this->setting('token'))
            ->acceptJson()
            ->timeout(30);
    }

    protected function setting(string $key): string
    {
        $value = (string) ($this->config[$key] ?? '');

        if ($value === '') {
            throw new RuntimeException("Set tembo.sentry.{$key} (see the Sentry section of config/tembo.php).");
        }

        return $value;
    }
}
