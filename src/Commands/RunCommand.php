<?php

namespace Cabuz\Tembo\Commands;

use Cabuz\Tembo\Exceptions\SessionTimeoutException;
use Cabuz\Tembo\Exceptions\TemboException;
use Cabuz\Tembo\Support\SessionState;
use Cabuz\Tembo\Tembo;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RunCommand extends Command
{
    protected $signature = 'tembo:run
        {task : What the coding agent should do}
        {--repo=* : Repository ID or owner/name (repeatable)}
        {--agent= : Agent and model key, e.g. claudeCode:claude-fable-5-1}
        {--project= : Project ID}
        {--branch= : Base branch to start from}
        {--wait : Wait until the session finishes}
        {--follow : Stream the agent output until the session finishes (needs phrity/websocket)}
        {--timeout=3600 : Seconds to wait with --wait or --follow}';

    protected $description = 'Start a Tembo coding agent session';

    public function handle(Tembo $tembo): int
    {
        try {
            $repositoryIds = $this->repositoryIds($tembo);

            if ($repositoryIds === null) {
                return self::FAILURE;
            }

            $session = $tembo->sessions()->start((string) $this->argument('task'), array_filter([
                'codeRepositoryIds' => $repositoryIds,
                'agent' => $this->option('agent'),
                'projectId' => $this->option('project'),
                'baseBranch' => $this->option('branch'),
            ], fn ($value) => $value !== null && $value !== []));

            $id = (string) ($session['id'] ?? '');
            $this->components->info('Session started: '.$id);

            if (isset($session['htmlUrl']) && is_string($session['htmlUrl'])) {
                $this->line('  '.$session['htmlUrl']);
            }

            if ($this->option('follow') && $id !== '') {
                $session = $this->follow($tembo, $id);
            } elseif (! $this->option('wait') || $id === '') {
                return self::SUCCESS;
            } else {
                $this->components->task('Waiting for the agent', function () use ($tembo, $id, &$session) {
                    $session = $tembo->sessions()->wait($id, (int) $this->option('timeout'));

                    return SessionState::of($session) === SessionState::COMPLETED;
                });
            }
        } catch (TemboException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $state = SessionState::of($session);
        $this->components->twoColumnDetail('State', (string) $state);

        return $state === SessionState::COMPLETED ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Print the session's live output until it finishes, checking its state
     * whenever the stream goes quiet.
     *
     * @return array<string, mixed>
     */
    protected function follow(Tembo $tembo, string $id): array
    {
        $deadline = time() + (int) $this->option('timeout');
        $session = [];

        foreach ($tembo->sessions()->events()->live()->subscribe($id) as $frame) {
            if ($frame['type'] === 'delta' && is_array($frame['data'] ?? null)) {
                $this->line((string) ($frame['data']['message'] ?? ''));

                continue;
            }

            if ($frame['type'] === 'idle') {
                $session = $tembo->sessions()->retrieve($id);

                if (SessionState::isFinished($session)) {
                    return $session;
                }

                if (time() >= $deadline) {
                    throw SessionTimeoutException::make($id, (int) $this->option('timeout'), $session);
                }
            }
        }

        return $session;
    }

    /**
     * @return list<string>|null null when a repository name couldn't be resolved.
     */
    protected function repositoryIds(Tembo $tembo): ?array
    {
        $ids = [];

        foreach ((array) $this->option('repo') as $repo) {
            $repo = (string) $repo;

            if (Str::isUuid($repo)) {
                $ids[] = $repo;

                continue;
            }

            $repository = $tembo->repositories()->findByName($repo);

            if ($repository === null || ! is_string($repository['id'] ?? null)) {
                $this->components->error("Repository [{$repo}] was not found in Tembo.");

                return null;
            }

            $ids[] = $repository['id'];
        }

        return $ids;
    }
}
