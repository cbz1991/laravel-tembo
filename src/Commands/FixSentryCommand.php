<?php

namespace Cabuz\Tembo\Commands;

use Cabuz\Tembo\Commands\Concerns\ResolvesRepositories;
use Cabuz\Tembo\Exceptions\NotFoundException;
use Cabuz\Tembo\Support\Sentry;
use Cabuz\Tembo\Tembo;
use Illuminate\Console\Command;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Str;
use RuntimeException;

class FixSentryCommand extends Command
{
    use ResolvesRepositories;

    protected $signature = 'tembo:fix-sentry
        {--limit=5 : The most Sentry issues to send}
        {--query=is:unresolved : Sentry search query for the issues}
        {--repo=* : Repository ID or owner/name (defaults to tembo.sentry.repository)}
        {--agent= : Agent and model key, e.g. claudeCode:claude-fable-5-1}
        {--project= : Project ID}
        {--branch= : Base branch to start from}
        {--dry-run : List the issues without starting sessions}';

    protected $description = 'Start a Tembo session that fixes each unresolved Sentry issue, on its own branch';

    public function handle(Tembo $tembo, Sentry $sentry): int
    {
        try {
            $repositories = (array) $this->option('repo') ?: array_filter(explode(',', (string) config('tembo.sentry.repository')));
            $repositoryIds = $this->repositoryIds($tembo, array_map('trim', $repositories));

            if ($repositoryIds === null) {
                return self::FAILURE;
            }

            if ($repositoryIds === []) {
                $this->components->error('Pass --repo or set tembo.sentry.repository: the repository the fixes go to.');

                return self::FAILURE;
            }

            $issues = $sentry->issues((string) $this->option('query'), max(1, (int) $this->option('limit')));

            if ($issues === []) {
                $this->components->info('No Sentry issues match ['.$this->option('query').'].');

                return self::SUCCESS;
            }

            foreach ($issues as $issue) {
                $this->fix($tembo, $sentry, $issue, $repositoryIds);
            }
        } catch (HttpClientException|RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Start a session for one issue, unless an earlier run already did.
     *
     * @param  array<string, mixed>  $issue
     * @param  list<string>  $repositoryIds
     */
    protected function fix(Tembo $tembo, Sentry $sentry, array $issue, array $repositoryIds): void
    {
        $label = ($issue['shortId'] ?? $issue['id'] ?? '?').' '.($issue['title'] ?? '');
        $sessionId = $this->sessionId((string) ($issue['id'] ?? ''));

        try {
            $tembo->sessions()->retrieve($sessionId);
            $this->components->twoColumnDetail($label, 'already sent');

            return;
        } catch (NotFoundException) {
            // No session for this issue yet.
        }

        $branch = config('tembo.sentry.branch_prefix', 'fix/sentry-').Str::slug((string) ($issue['shortId'] ?? $issue['id'] ?? ''));

        if ($this->option('dry-run')) {
            $this->components->twoColumnDetail($label, $branch);

            return;
        }

        $session = $tembo->sessions()->start($sentry->task($issue, $sentry->latestEvent((string) $issue['id'])), array_filter([
            'id' => $sessionId,
            'branchName' => $branch,
            'codeRepositoryIds' => $repositoryIds,
            'agent' => $this->option('agent'),
            'projectId' => $this->option('project'),
            'baseBranch' => $this->option('branch'),
        ], fn ($value) => $value !== null && $value !== []));

        $this->components->twoColumnDetail($label, (string) ($session['htmlUrl'] ?? $session['id'] ?? $sessionId));
    }

    /**
     * The same session ID every run for the same issue (a UUID v5), so an
     * issue is only sent once, from any machine.
     */
    protected function sessionId(string $issueId): string
    {
        $name = 'sentry:'.config('tembo.sentry.organization').':'.$issueId;
        $hash = sha1(hex2bin(str_replace('-', '', '6ba7b811-9dad-11d1-80b4-00c04fd430c8')).$name);

        return sprintf(
            '%s-%s-%04x-%04x-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0FFF) | 0x5000,
            (hexdec(substr($hash, 16, 4)) & 0x3FFF) | 0x8000,
            substr($hash, 20, 12),
        );
    }
}
