<?php

namespace Cabuz\Tembo\Commands;

use Cabuz\Tembo\Commands\Concerns\ResolvesRepositories;
use Cabuz\Tembo\Exceptions\SessionTimeoutException;
use Cabuz\Tembo\Exceptions\TemboException;
use Cabuz\Tembo\Support\LiveOutput;
use Cabuz\Tembo\Support\SessionState;
use Cabuz\Tembo\Tembo;
use Illuminate\Console\Command;

class RunCommand extends Command
{
    use ResolvesRepositories;

    protected $signature = 'tembo:run
        {task : What the coding agent should do}
        {--repo=* : Repository ID or owner/name (repeatable)}
        {--agent= : Agent and model key, e.g. claudeCode:claude-fable-5-1}
        {--project= : Project ID}
        {--branch= : Base branch to start from}
        {--wait : Wait until the session finishes}
        {--follow : Stream the agent output until the session finishes (needs phrity/websocket)}
        {--raw : With --follow, print the raw provider events instead of readable text}
        {--timeout=3600 : Seconds to wait with --wait or --follow}';

    protected $description = 'Start a Tembo coding agent session';

    public function handle(Tembo $tembo): int
    {
        try {
            $repositoryIds = $this->repositoryIds($tembo, (array) $this->option('repo'));

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
        $output = $this->option('raw') ? null : new LiveOutput;

        foreach ($tembo->sessions()->events()->live()->subscribe($id) as $frame) {
            if ($frame['type'] === 'delta' && is_array($frame['data'] ?? null)) {
                if ($output === null) {
                    $this->line((string) ($frame['data']['message'] ?? ''));
                } else {
                    $this->output->write($output->render($frame['data']));
                }

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
}
